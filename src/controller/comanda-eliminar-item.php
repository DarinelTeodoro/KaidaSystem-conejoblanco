<?php
include('../model/conexion.php');
$id_item = $_POST['id_item'];
$id_comanda = $_POST['id_comanda'];
$destino = $_POST['destino'];

$conexion = new Conexion();

function actualizarEstadoComanda($conexion, $id_comanda, $id_item, $destino) {
    
    if ($destino === 'Ambos') {
        // Consulta única para obtener estados de cocina y barra simultáneamente
        $stmt = $conexion->prepare('
            SELECT 
                SUM(CASE WHEN destino = "Cocina" THEN 1 ELSE 0 END) AS total_cocina,
                SUM(CASE WHEN destino = "Cocina" AND ready_cocina = 0 THEN 1 ELSE 0 END) AS pendientes_cocina,
                SUM(CASE WHEN destino = "Barra" THEN 1 ELSE 0 END) AS total_barra,
                SUM(CASE WHEN destino = "Barra" AND ready_barra = 0 THEN 1 ELSE 0 END) AS pendientes_barra
            FROM comanda_items 
            WHERE comanda_id = :comanda AND id != :item
        ');
        $stmt->bindParam(':comanda', $id_comanda);
        $stmt->bindParam(':item', $id_item);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcular estados
        $estado_cocina = $resultado['total_cocina'] == 0 ? 0 : ($resultado['pendientes_cocina'] > 0 ? 1 : 2);
        $estado_barra = $resultado['total_barra'] == 0 ? 0 : ($resultado['pendientes_barra'] > 0 ? 1 : 2);
        
        // Actualizar ambas columnas
        $update = $conexion->prepare('
            UPDATE comandas 
            SET cocina = :cocina, barra = :barra 
            WHERE id = :comanda
        ');
        $update->bindParam(':cocina', $estado_cocina);
        $update->bindParam(':barra', $estado_barra);
        $update->bindParam(':comanda', $id_comanda);
        $update->execute();
        
    } else {
        // Destino específico (Cocina o Barra)
        $ready_columna = ($destino === 'Cocina') ? 'ready_cocina' : 'ready_barra';
        $columna_update = ($destino === 'Cocina') ? 'cocina' : 'barra';
        
        // Consulta única para este destino
        $stmt = $conexion->prepare("
            SELECT 
                COUNT(id) AS total,
                SUM(CASE WHEN $ready_columna = 0 THEN 1 ELSE 0 END) AS pendientes
            FROM comanda_items 
            WHERE comanda_id = :comanda 
                AND destino = :destino 
                AND id != :item
        ");
        $stmt->bindParam(':comanda', $id_comanda);
        $stmt->bindParam(':destino', $destino);
        $stmt->bindParam(':item', $id_item);
        $stmt->execute();
        
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcular estado
        $estado = $resultado['total'] == 0 ? 0 : ($resultado['pendientes'] > 0 ? 1 : 2);
        
        // Actualizar columna
        $update = $conexion->prepare("
            UPDATE comandas 
            SET $columna_update = :estado 
            WHERE id = :comanda
        ");
        $update->bindParam(':estado', $estado);
        $update->bindParam(':comanda', $id_comanda);
        $update->execute();
    }
}

try {
    $conexion->beginTransaction();

    actualizarEstadoComanda($conexion, $id_comanda, $id_item, $destino);

    // Deshabilitar foreign keys
    $conexion->exec("SET FOREIGN_KEY_CHECKS=0");

    // Eliminar componentes
    $stmt = $conexion->prepare('DELETE FROM comanda_item_componentes WHERE item_id = :id');
    $stmt->bindParam(':id', $id_item);
    $stmt->execute();

    // Eliminar item
    $query = $conexion->prepare('DELETE FROM comanda_items WHERE id = :id');
    $query->bindParam(':id', $id_item);
    $query->execute();

    // Reactivar foreign keys
    $conexion->exec("SET FOREIGN_KEY_CHECKS=1");

    


    $conexion->commit();
    echo $id_comanda;

} catch (PDOException $e) {
    $conexion->rollBack();
    $conexion->exec("SET FOREIGN_KEY_CHECKS=1");
    //echo "Error: " . $e->getMessage();
    echo $id_comanda;
}
?>