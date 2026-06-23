<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
class OrderController {
    public static function getAll(?string $status=null): array {
        $tid=AuthController::tenantId();
        $where='WHERE o.tenant_id=:tid'; $params=[':tid'=>$tid];
        if ($status && in_array($status,ORDER_STATUSES,true)) { $where.=' AND o.order_status=:status'; $params[':status']=$status; }
        $sql="SELECT o.*,s.name AS supplier_name,s.contact_email AS supplier_email,COUNT(oi.id) AS item_count FROM orders o JOIN suppliers s ON s.id=o.supplier_id LEFT JOIN order_items oi ON oi.order_id=o.id $where GROUP BY o.id ORDER BY o.created_at DESC";
        return DB::query($sql,$params)->fetchAll();
    }
    public static function getById(int $id): ?array {
        $tid=AuthController::tenantId();
        $order=DB::query('SELECT o.*,s.name AS supplier_name,s.contact_email AS supplier_email,s.phone AS supplier_phone,s.address AS supplier_address FROM orders o JOIN suppliers s ON s.id=o.supplier_id WHERE o.id=:id AND o.tenant_id=:tid LIMIT 1',[':id'=>$id,':tid'=>$tid])->fetch();
        if (!$order) return null;
        $items=DB::query('SELECT oi.*,p.name AS product_name,p.sku FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=:oid',[':oid'=>$id])->fetchAll();
        $order['items']=$items;
        return $order;
    }
    public static function countPending(): int {
        $tid=AuthController::tenantId();
        return (int)DB::query("SELECT COUNT(*) FROM orders WHERE tenant_id=:tid AND order_status IN ('draft','pending','ordered')",[':tid'=>$tid])->fetchColumn();
    }
    public static function draftExistsForProduct(int $productId): bool {
        $tid=AuthController::tenantId();
        return (int)DB::query("SELECT COUNT(*) FROM orders o JOIN order_items oi ON oi.order_id=o.id WHERE o.tenant_id=:tid AND oi.product_id=:pid AND o.order_status IN ('draft','pending')",[':tid'=>$tid,':pid'=>$productId])->fetchColumn()>0;
    }
    public static function createDraftOrder(int $supplierId, string $notes, float $totalCost, array $items): int {
        $tid=AuthController::tenantId();
        try {
            DB::beginTransaction();
            DB::query('INSERT INTO orders(tenant_id,supplier_id,order_status,total_cost,notes) VALUES(:tid,:sid,:status,:cost,:notes)',[':tid'=>$tid,':sid'=>$supplierId,':status'=>ORDER_STATUS_DRAFT,':cost'=>$totalCost,':notes'=>$notes]);
            $orderId=(int)DB::lastInsertId();
            foreach ($items as $productId=>$item) {
                DB::query('INSERT INTO order_items(order_id,product_id,quantity,unit_cost) VALUES(:oid,:pid,:qty,:cost)',[':oid'=>$orderId,':pid'=>$productId,':qty'=>(int)$item['qty'],':cost'=>(float)$item['cost']]);
            }
            DB::commit();
            return $orderId;
        } catch (Exception $e) { DB::rollBack(); error_log('[OrderController] '.$e->getMessage()); return 0; }
    }
    public static function create(array $data, array $items): int { return self::createDraftOrder((int)$data['supplier_id'],trim($data['notes']??''),(float)$data['total_cost'],$items); }
    public static function updateStatus(int $id, string $status): int {
        if (!in_array($status,ORDER_STATUSES,true)) return 0;
        $tid=AuthController::tenantId();
        if ($status===ORDER_STATUS_RECEIVED) self::receiveOrderStock($id,$tid);
        return DB::query('UPDATE orders SET order_status=:status WHERE id=:id AND tenant_id=:tid',[':status'=>$status,':id'=>$id,':tid'=>$tid])->rowCount();
    }
    private static function receiveOrderStock(int $orderId, int $tid): void {
        require_once __DIR__.'/StockController.php';
        $items=DB::query('SELECT oi.product_id,oi.quantity,p.stock_level FROM order_items oi JOIN products p ON p.id=oi.product_id WHERE oi.order_id=:oid',[':oid'=>$orderId])->fetchAll();
        foreach ($items as $item) StockController::updateStock((int)$item['product_id'],(int)$item['stock_level']+(int)$item['quantity'],'Stock received from order #'.$orderId);
    }
    public static function delete(int $id): bool {
        $tid=AuthController::tenantId();
        DB::query('DELETE FROM orders WHERE id=:id AND tenant_id=:tid AND order_status=:s',[':id'=>$id,':tid'=>$tid,':s'=>ORDER_STATUS_DRAFT]);
        return true;
    }
}
