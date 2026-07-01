<?php

/**
 * Servicio para manejo de imágenes
 * 
 * Gestiona la carga, procesamiento y eliminación de imágenes
 * 
 * @version 1.0
 */
class ImagenService
{
    /**
     * Directorio de carga de imágenes
     * @var string
     */
    private $upload_dir;

    /**
     * Tipos de imágenes permitidos
     * @var array
     */
    private $allowed_types;

    /**
     * Tamaño máximo de archivo permitido (en bytes)
     * @var int
     */
    private $max_size = 5242880; // 5MB

    /**
     * Constructor de la clase
     * 
     * @param string $upload_dir Directorio de carga de imágenes
     */
    public function __construct($upload_dir)
    {
        $this->upload_dir = $upload_dir;
        $this->allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/jpg'];

        // Crear directorio si no existe
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }

    /**
     * Procesa una imagen subida
     * 
     * @param array $imagen Datos de la imagen subida
     * @return string|bool Nombre del archivo o false si hubo un error
     */
    public function procesarImagen($imagen)
    {
        // Si no hay imagen o hay error, retornar false
        if (!$imagen || $imagen['error'] != 0) {
            return false;
        }

        // Verificar tipo de archivo
        if (!in_array($imagen['type'], $this->allowed_types)) {
            return false;
        }

        // Verificar tamaño de archivo
        if ($imagen['size'] > $this->max_size) {
            return false;
        }

        // Obtener información del archivo
        $extension = pathinfo($imagen['name'], PATHINFO_EXTENSION);
        $nombre_sin_extension = pathinfo($imagen['name'], PATHINFO_FILENAME);

        // Sanitizar nombre del archivo
        $nombre_sin_extension = preg_replace('/[^a-zA-Z0-9_-]/', '', $nombre_sin_extension);

        // Generar nombre único
        $nombre_archivo = uniqid() . '_' . $nombre_sin_extension . '.' . $extension;

        // Ruta completa
        $ruta_destino = $this->upload_dir . $nombre_archivo;

        // Mover archivo
        if (move_uploaded_file($imagen['tmp_name'], $ruta_destino)) {
            return $nombre_archivo; // Retornar solo el nombre para guardar en BD
        }

        return false;
    }

    /**
     * Elimina una imagen
     * 
     * @param string $nombre_archivo Nombre del archivo a eliminar
     * @return bool True si se eliminó correctamente, False en caso contrario
     */
    public function eliminarImagen($nombre_archivo)
    {
        // No eliminar imagen predeterminada
        if (!$nombre_archivo || $nombre_archivo == 'user_default.jpg') {
            return true;
        }

        $ruta_completa = $this->upload_dir . $nombre_archivo;
        if (file_exists($ruta_completa)) {
            return unlink($ruta_completa);
        }

        return true; // Si no existe, consideramos que ya está eliminada
    }

    /**
     * Redimensiona una imagen
     * 
     * @param string $imagen_path Ruta de la imagen
     * @param int $ancho Ancho deseado
     * @param int $alto Alto deseado
     * @return bool True si se redimensionó correctamente, False en caso contrario
     */
    public function redimensionarImagen($imagen_path, $ancho = 200, $alto = 200)
    {
        // Verificar que la imagen existe
        if (!file_exists($imagen_path)) {
            return false;
        }

        // Obtener información de la imagen
        $info = getimagesize($imagen_path);
        if (!$info) {
            return false;
        }

        // Crear imagen según el tipo
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $imagen = imagecreatefromjpeg($imagen_path);
                break;
            case IMAGETYPE_PNG:
                $imagen = imagecreatefrompng($imagen_path);
                break;
            case IMAGETYPE_GIF:
                $imagen = imagecreatefromgif($imagen_path);
                break;
            case IMAGETYPE_WEBP:
                $imagen = imagecreatefromwebp($imagen_path);
                break;
            default:
                return false;
        }

        // Calcular dimensiones manteniendo la proporción
        $ancho_original = imagesx($imagen);
        $alto_original = imagesy($imagen);

        $ratio = $ancho_original / $alto_original;

        if ($ancho / $alto > $ratio) {
            $ancho = $alto * $ratio;
        } else {
            $alto = $ancho / $ratio;
        }

        // Crear imagen redimensionada
        $imagen_redimensionada = imagecreatetruecolor($ancho, $alto);

        // Preservar transparencia en PNG
        if ($info[2] == IMAGETYPE_PNG) {
            imagealphablending($imagen_redimensionada, false);
            imagesavealpha($imagen_redimensionada, true);
            $transparent = imagecolorallocatealpha($imagen_redimensionada, 255, 255, 255, 127);
            imagefilledrectangle($imagen_redimensionada, 0, 0, $ancho, $alto, $transparent);
        }

        // Redimensionar
        imagecopyresampled($imagen_redimensionada, $imagen, 0, 0, 0, 0, $ancho, $alto, $ancho_original, $alto_original);

        // Guardar imagen
        $resultado = false;
        switch ($info[2]) {
            case IMAGETYPE_JPEG:
                $resultado = imagejpeg($imagen_redimensionada, $imagen_path, 90);
                break;
            case IMAGETYPE_PNG:
                $resultado = imagepng($imagen_redimensionada, $imagen_path, 9);
                break;
            case IMAGETYPE_GIF:
                $resultado = imagegif($imagen_redimensionada, $imagen_path);
                break;
            case IMAGETYPE_WEBP:
                $resultado = imagewebp($imagen_redimensionada, $imagen_path, 90);
                break;
        }

        // Liberar memoria
        imagedestroy($imagen);
        imagedestroy($imagen_redimensionada);

        return $resultado;
    }
}
