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
        // Mapa de tipo MIME real (detectado en el servidor) -> extensión segura a usar en disco
        $this->allowed_types = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        // Crear directorio si no existe
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
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

        // Verificar tamaño de archivo
        if ($imagen['size'] > $this->max_size) {
            return false;
        }

        // Detectar el tipo MIME real inspeccionando el contenido del archivo
        // (el 'type' del $_FILES es reportado por el cliente y no es confiable).
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $tipo_real = finfo_file($finfo, $imagen['tmp_name']);
        finfo_close($finfo);

        if (!isset($this->allowed_types[$tipo_real])) {
            return false;
        }

        // Verificar además que sea una imagen válida y no un archivo disfrazado
        if (@getimagesize($imagen['tmp_name']) === false) {
            return false;
        }

        // La extensión se determina únicamente por el tipo MIME real detectado,
        // nunca por el nombre de archivo enviado por el cliente.
        $extension = $this->allowed_types[$tipo_real];

        // Generar nombre único
        $nombre_archivo = uniqid('img_', true) . '.' . $extension;

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

        // El nombre viene de la BD: filas antiguas pudieron guardar texto del POST
        // (p. ej. '../../.env'), así que solo se acepta un nombre sin directorios
        if ($nombre_archivo !== basename($nombre_archivo)) {
            return false;
        }

        $ruta_completa = $this->upload_dir . $nombre_archivo;
        if (file_exists($ruta_completa)) {
            return unlink($ruta_completa);
        }

        return true; // Si no existe, consideramos que ya está eliminada
    }

}
