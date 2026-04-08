<?php
include('../model/querys.php');

// Configurar header para respuesta JSON
header('Content-Type: application/json');

try {
    // Validar que llegaron todos los datos
    if (!isset($_POST['estado']) || !isset($_POST['tipo']) || !isset($_POST['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Faltan datos requeridos'
        ]);
        exit;
    }
    
    $disponibilidad = $_POST['estado'];
    $tipo = $_POST['tipo'];
    $id_item = $_POST['id'];
    
    // Validar que el ID sea numérico
    if (!is_numeric($id_item)) {
        echo json_encode([
            'success' => false,
            'message' => 'ID inválido'
        ]);
        exit;
    }
    
    $conexion = new Conexion();
    $query = null;
    
    // Seleccionar la tabla según el tipo
    switch($tipo) {
        case 'combo':
            $query = $conexion->prepare('UPDATE menu_combos SET disponibilidad = :disponibilidad WHERE id = :id');
            break;
        case 'producto':
            $query = $conexion->prepare('UPDATE menu_productos SET disponibilidad = :disponibilidad WHERE id = :id');
            break;
        case 'extra':
            $query = $conexion->prepare('UPDATE menu_extras SET disponibilidad = :disponibilidad WHERE id = :id');
            break;
        case 'variante':
            $query = $conexion->prepare('UPDATE menu_variantes SET disponibilidad = :disponibilidad WHERE id = :id');
            break;
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Tipo de elemento no válido'
            ]);
            exit;
    }
    
    // Ejecutar la consulta
    $query->bindParam(':disponibilidad', $disponibilidad);
    $query->bindParam(':id', $id_item);
    $query->execute();
    
    // Verificar si se actualizó alguna fila
    if ($query->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Actualizado correctamente',
            'data' => [
                'tipo' => $tipo,
                'id' => $id_item,
                'estado' => $disponibilidad
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No se encontró el registro o no hubo cambios'
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error general: ' . $e->getMessage()
    ]);
}
?>