<?php
session_start();
date_default_timezone_set('America/Mexico_City');
header('Content-Type: application/json');

include('../model/conexion.php');
$fecha = date('Y-m-d H:i:s');

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }
    
    // Obtener y validar el ID de la comanda
    $comanda_id = isset($_POST['comanda_id']) ? intval($_POST['comanda_id']) : 0;
    
    if (!$comanda_id) {
        throw new Exception('ID de comanda no válido');
    }
    
    $cnx = new Conexion();
    
    // Iniciar transacción
    $cnx->beginTransaction();
    
    // Verificar que la comanda existe y no está ya completada
    $stmtCheck = $cnx->prepare("SELECT id, estado FROM comandas WHERE id = :id");
    $stmtCheck->execute([':id' => $comanda_id]);
    $comanda = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$comanda) {
        throw new Exception('La comanda no existe');
    }
    
    if ($comanda['estado'] === 'finalizado') {
        throw new Exception('La comanda ya está completada');
    }
    
    // Actualizar el estado de la comanda a 'completado'
    $stmtUpdate = $cnx->prepare("
        UPDATE comandas 
        SET barra = '2', 
            finished_at = :fecha 
        WHERE id = :id
    ");
    
    $result = $stmtUpdate->execute([':id' => $comanda_id, ':fecha' => $fecha]);

    $stmtUpdateItmes = $cnx->prepare("
    UPDATE comanda_items 
    SET ready_barra = 1
    WHERE comanda_id = :id AND destino IN ('Barra', 'Ambos')
    ");

    $resultitems = $stmtUpdateItmes->execute([':id' => $comanda_id]);
    
    if (!$result) {
        throw new Exception('Error al actualizar la comanda');
    }

    if (!$resultitems) {
        throw new Exception('Error al completar la comanda');
    }
    
    /* Opcional: Registrar en una bitácora de actividades
    $userId = $_SESSION['user_id'] ?? null; // Si tienes sesión de usuario
    
    if ($userId) {
        $stmtLog = $cnx->prepare("
            INSERT INTO comandas_log (comanda_id, user_id, accion, fecha) 
            VALUES (:comanda_id, :user_id, 'completado', NOW())
        ");
        $stmtLog->execute([
            ':comanda_id' => $comanda_id,
            ':user_id' => $userId
        ]);
    }*/
    
    // Confirmar transacción
    $cnx->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Comanda completada exitosamente',
        'comanda_id' => $comanda_id
    ]);
    
} catch (Exception $e) {
    // Revertir cambios en caso de error
    if (isset($cnx) && $cnx->inTransaction()) {
        $cnx->rollBack();
    }
    
    error_log("Error al completar comanda: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>