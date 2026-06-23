<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/OrderController.php';
require_once __DIR__ . '/../controllers/StockController.php';
class ProductController {
    public static function getAll(string $search='', ?int $tenantId=null): array {
        $tid=$tenantId ?? AuthController::tenantId(); $search=trim($search);
        if ($search!=='') {
            $like='%'.$search.'%';
            return DB::query('SELECT p.*,s.name AS supplier_name FROM products p JOIN suppliers s ON s.id=p.supplier_id WHERE p.tenant_id=:tid AND p.is_active=1 AND (p.name LIKE :q OR p.sku LIKE :q2 OR p.category LIKE :q3) ORDER BY p.name ASC',[':tid'=>$tid,':q'=>$like,':q2'=>$like,':q3'=>$like])->fetchAll();
        }
        return DB::query('SELECT p.*,s.name AS supplier_name FROM products p JOIN suppliers s ON s.id=p.supplier_id WHERE p.tenant_id=:tid AND p.is_active=1 ORDER BY p.name ASC',[':tid'=>$tid])->fetchAll();
    }
    public static function getById(int $id): ?array {
        $tid=AuthController::tenantId();
        $row=DB::query('SELECT p.*,s.name AS supplier_name FROM products p JOIN suppliers s ON s.id=p.supplier_id WHERE p.id=:id AND p.tenant_id=:tid AND p.is_active=1 LIMIT 1',[':id'=>$id,':tid'=>$tid])->fetch();
        return $row?:null;
    }
    public static function getLowStock(): array {
        $tid=AuthController::tenantId();
        return DB::query('SELECT p.*,s.name AS supplier_name FROM products p JOIN suppliers s ON s.id=p.supplier_id WHERE p.tenant_id=:tid AND p.is_active=1 AND p.stock_level<p.reorder_level ORDER BY (p.reorder_level-p.stock_level) DESC',[':tid'=>$tid])->fetchAll();
    }
    public static function countLowStock(): int {
        $tid=AuthController::tenantId();
        return (int)DB::query('SELECT COUNT(*) FROM products WHERE tenant_id=:tid AND is_active=1 AND stock_level<reorder_level',[':tid'=>$tid])->fetchColumn();
    }
    public static function countAll(): int {
        $tid=AuthController::tenantId();
        return (int)DB::query('SELECT COUNT(*) FROM products WHERE tenant_id=:tid AND is_active=1',[':tid'=>$tid])->fetchColumn();
    }
    public static function totalInventoryValue(): float {
        $tid=AuthController::tenantId();
        return (float)DB::query('SELECT COALESCE(SUM(stock_level*unit_price),0) FROM products WHERE tenant_id=:tid AND is_active=1',[':tid'=>$tid])->fetchColumn();
    }
    public static function create(array $data): int {
        $tid=AuthController::tenantId();
        DB::query('INSERT INTO products(tenant_id,supplier_id,name,sku,description,stock_level,reorder_level,unit_price,category) VALUES(:tid,:supplier_id,:name,:sku,:description,:stock_level,:reorder_level,:unit_price,:category)',[':tid'=>$tid,':supplier_id'=>(int)$data['supplier_id'],':name'=>trim($data['name']),':sku'=>trim($data['sku']),':description'=>trim($data['description']??''),':stock_level'=>(int)$data['stock_level'],':reorder_level'=>(int)$data['reorder_level'],':unit_price'=>(float)$data['unit_price'],':category'=>trim($data['category']??'')]);
        $newId=(int)DB::lastInsertId();
        StockController::record($newId,0,(int)$data['stock_level'],'Initial stock on product creation');
        self::checkAndTriggerDraftPO($newId);
        return $newId;
    }
    public static function update(int $id, array $data): int {
        $tid=AuthController::tenantId();
        return DB::query('UPDATE products SET supplier_id=:supplier_id,name=:name,sku=:sku,description=:description,reorder_level=:reorder_level,unit_price=:unit_price,category=:category WHERE id=:id AND tenant_id=:tid',[':supplier_id'=>(int)$data['supplier_id'],':name'=>trim($data['name']),':sku'=>trim($data['sku']),':description'=>trim($data['description']??''),':reorder_level'=>(int)$data['reorder_level'],':unit_price'=>(float)$data['unit_price'],':category'=>trim($data['category']??''),':id'=>$id,':tid'=>$tid])->rowCount();
    }
    public static function delete(int $id): bool {
        $tid=AuthController::tenantId();
        DB::query('UPDATE products SET is_active=0 WHERE id=:id AND tenant_id=:tid',[':id'=>$id,':tid'=>$tid]);
        return true;
    }
    public static function runLowStockCheck(): int {
        $lowItems=self::getLowStock(); $created=0;
        foreach ($lowItems as $p) { if (self::checkAndTriggerDraftPO((int)$p['id'])) $created++; }
        return $created;
    }
    public static function checkAndTriggerDraftPO(int $productId): bool {
        $product=self::getById($productId);
        if (!$product) return false;
        if ($product['stock_level']>=$product['reorder_level']) return false;
        if (OrderController::draftExistsForProduct($productId)) return false;
        $reorderQty=(int)$product['reorder_level']*REORDER_MULTIPLIER;
        $totalCost=$reorderQty*(float)$product['unit_price'];
        $orderId=OrderController::createDraftOrder((int)$product['supplier_id'],'Auto-generated: '.$product['sku'].' below reorder level ('.$product['stock_level'].'/'.$product['reorder_level'].')',$totalCost,[(int)$productId=>['qty'=>$reorderQty,'cost'=>(float)$product['unit_price']]]);
        return $orderId>0;
    }
}
