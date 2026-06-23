<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/StockController.php';
AuthController::startSession();
AuthController::requireAuth();
header('Content-Type: application/json; charset=utf-8');
try {
    $days = isset($_GET['days']) ? max(7,min(90,(int)$_GET['days'])) : CHART_DAYS;
    $trend = StockController::getDailyValueTrend($days);
    $labels=[]; $valueData=[]; $unitsData=[];
    foreach ($trend as $row) { $labels[]=date('M d',strtotime($row['chart_date'])); $valueData[]=round((float)$row['total_value'],2); $unitsData[]=(int)$row['total_units']; }
    echo json_encode(['success'=>true,'days'=>$days,'labels'=>$labels,'datasets'=>[['id'=>'stock_value','label'=>'Stock Value (USD)','data'=>$valueData,'color'=>'#6366f1'],['id'=>'stock_units','label'=>'Total Units','data'=>$unitsData,'color'=>'#22d3ee']]],JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK);
} catch (Throwable $e) { http_response_code(500); error_log('[API/stock_chart] '.$e->getMessage()); echo json_encode(['success'=>false,'error'=>ERR_SERVER]); }
