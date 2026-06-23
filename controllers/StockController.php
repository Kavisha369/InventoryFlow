<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
class StockController {
    public static function updateStock(int $productId, int $newStock, string $note = ''): bool {
        $tid = AuthController::tenantId();
        $current = DB::query('SELECT stock_level FROM products WHERE id=:id AND tenant_id=:tid LIMIT 1',[':id'=>$productId,':tid'=>$tid])->fetchColumn();
        if ($current===false) return false;
        $oldStock=(int)$current;
        try {
            DB::beginTransaction();
            DB::query('UPDATE products SET stock_level=:new WHERE id=:id AND tenant_id=:tid',[':new'=>$newStock,':id'=>$productId,':tid'=>$tid]);
            self::record($productId,$oldStock,$newStock,$note);
            DB::commit();
            return true;
        } catch (Exception $e) { DB::rollBack(); error_log('[StockController] '.$e->getMessage()); return false; }
    }
    public static function adjustStock(int $productId, int $delta, string $note=''): bool {
        $tid=AuthController::tenantId();
        $current=DB::query('SELECT stock_level FROM products WHERE id=:id AND tenant_id=:tid LIMIT 1',[':id'=>$productId,':tid'=>$tid])->fetchColumn();
        if ($current===false) return false;
        return self::updateStock($productId,max(0,(int)$current+$delta),$note);
    }
    public static function record(int $productId, int $oldStock, int $newStock, string $note=''): void {
        $tid=AuthController::tenantId();
        $userId=AuthController::userId()?:null;
        DB::query('INSERT INTO stock_history(tenant_id,product_id,old_stock,new_stock,changed_by,change_note) VALUES(:tid,:pid,:old,:new,:uid,:note)',[':tid'=>$tid,':pid'=>$productId,':old'=>$oldStock,':new'=>$newStock,':uid'=>$userId,':note'=>$note]);
    }
    public static function getHistory(int $productId, int $limit=30): array {
        $tid=AuthController::tenantId();
        return DB::query('SELECT * FROM stock_history WHERE tenant_id=:tid AND product_id=:pid ORDER BY recorded_at DESC LIMIT '.(int)$limit,[':tid'=>$tid,':pid'=>$productId])->fetchAll();
    }
    public static function getDailyValueTrend(int $days=30): array {
        $tid=AuthController::tenantId();
        $sql="SELECT DATE(sh.recorded_at) AS chart_date, SUM(sh.new_stock * p.unit_price) AS total_value, SUM(sh.new_stock) AS total_units FROM stock_history sh JOIN products p ON p.id=sh.product_id WHERE sh.tenant_id=:tid AND sh.recorded_at>=DATE_SUB(CURDATE(),INTERVAL :days DAY) GROUP BY DATE(sh.recorded_at) ORDER BY chart_date ASC";
        return DB::query($sql,[':tid'=>$tid,':days'=>$days])->fetchAll();
    }
}
