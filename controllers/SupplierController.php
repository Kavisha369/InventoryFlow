<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
class SupplierController {
    public static function getAll(): array {
        $tid=AuthController::tenantId();
        return DB::query('SELECT s.*,COUNT(p.id) AS product_count FROM suppliers s LEFT JOIN products p ON p.supplier_id=s.id AND p.is_active=1 WHERE s.tenant_id=:tid AND s.is_active=1 GROUP BY s.id ORDER BY s.name ASC',[':tid'=>$tid])->fetchAll();
    }
    public static function getById(int $id): ?array {
        $tid=AuthController::tenantId();
        $row=DB::query('SELECT * FROM suppliers WHERE id=:id AND tenant_id=:tid AND is_active=1 LIMIT 1',[':id'=>$id,':tid'=>$tid])->fetch();
        return $row?:null;
    }
    public static function countAll(): int {
        $tid=AuthController::tenantId();
        return (int)DB::query('SELECT COUNT(*) FROM suppliers WHERE tenant_id=:tid AND is_active=1',[':tid'=>$tid])->fetchColumn();
    }
    public static function create(array $data): int {
        $tid=AuthController::tenantId();
        DB::query('INSERT INTO suppliers(tenant_id,name,contact_email,phone,address) VALUES(:tid,:name,:email,:phone,:address)',[':tid'=>$tid,':name'=>trim($data['name']),':email'=>trim($data['contact_email']??''),':phone'=>trim($data['phone']??''),':address'=>trim($data['address']??'')]);
        return (int)DB::lastInsertId();
    }
    public static function update(int $id, array $data): int {
        $tid=AuthController::tenantId();
        return DB::query('UPDATE suppliers SET name=:name,contact_email=:email,phone=:phone,address=:address WHERE id=:id AND tenant_id=:tid',[':name'=>trim($data['name']),':email'=>trim($data['contact_email']??''),':phone'=>trim($data['phone']??''),':address'=>trim($data['address']??''),':id'=>$id,':tid'=>$tid])->rowCount();
    }
    public static function delete(int $id): bool {
        $tid=AuthController::tenantId();
        DB::query('UPDATE suppliers SET is_active=0 WHERE id=:id AND tenant_id=:tid',[':id'=>$id,':tid'=>$tid]);
        return true;
    }
}
