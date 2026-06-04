<?php

namespace App\Http\Repository;

use ErrorException;
use Illuminate\Support\Facades\File;
use RachidLaasri\LaravelInstaller\Middleware\canInstall;

class UtilityRepository
{
    public static function saveFile($file, $allowMimeTypeArray, $uploadPath = null)
    {
        if (!in_array($file->getClientMimeType(), $allowMimeTypeArray))
            throw new ErrorException("The file type is not allow. File type is: " . $file->getClientMimeType(), 400);
        $path = "uploadfiles";
        if ($uploadPath != null)
            $path = $uploadPath;
        $publicPath = public_path($path);
        if (!File::exists($publicPath)) {
            File::makeDirectory($publicPath, 0777, true, true);
        }
        $filePath = $file->store($path);
        return $filePath;
    }

    /**
     * Carpeta de almacenamiento de un paciente. Todo lo del paciente se
     * centraliza bajo una única carpeta raíz:
     *
     *   uploadfiles/adjuntos/{Nombre_Apellido}_{id}
     *
     * Si se indica $subdir, devuelve esa subcarpeta dentro de la del paciente:
     *
     *   patientFolder(87)                  → uploadfiles/adjuntos/Ana_Gerhardt_87
     *   patientFolder(87, 'seguimientos')  → uploadfiles/adjuntos/Ana_Gerhardt_87/seguimientos
     *
     * El nombre se decodifica (entidades del middleware xssProtection),
     * se translitera a ASCII (á→a, ñ→n) y se sanitiza para ser un nombre de
     * carpeta válido. Se añade el id del paciente como sufijo para evitar
     * colisiones entre homónimos. Devuelve null si no se puede resolver el
     * paciente (el caller decide el fallback).
     */
    public static function patientFolder($patientId, string $subdir = ''): ?string
    {
        $patientId = (int) $patientId;
        if ($patientId <= 0) return null;

        $patient = \App\Models\Patient\CmnPatient::find($patientId);
        if (!$patient) return null;

        $name = trim((string) $patient->full_name);
        $name = html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $ascii = \Illuminate\Support\Str::ascii($name);
        $slug  = preg_replace('/[^A-Za-z0-9]+/', '_', $ascii);
        $slug  = trim($slug, '_');
        if ($slug === '') $slug = 'paciente';

        $folder = 'uploadfiles/adjuntos/' . $slug . '_' . $patientId;

        if ($subdir !== '') {
            $folder .= '/' . trim($subdir, '/');
        }

        return $folder;
    }

    public static function hex2Rgba($color, $opacity = 1)
    {

        $default = 'rgb(0,0,0)';

        //Return default if no color provided
        if (empty($color))
            return $default;

        //Sanitize $color if "#" is provided 
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

        //Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
            $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) == 3) {
            $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return $default;
        }

        //Convert hexadec to rgb
        $rgb =  array_map('hexdec', $hex);

        //Check if opacity is set(rgba or rgb)
        if ($opacity) {
            if (abs($opacity) > 1)
                $opacity = 1.0;
            $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
        } else {
            $output = 'rgb(' . implode(",", $rgb) . ')';
        }

        //Return rgb(a) color string
        return $output;
    }

    /**
     * overWrite the Env File values.
     * @param  String type
     * @param  String value
     * @param  Bool forceAdd
     * @return \Illuminate\Http\Response
     */
    public static function overWriteEnvFile($type, $val, $forceAdd = false)
    {
        $path = base_path('.env');
        if (file_exists($path)) {
            $val = '"' . trim($val) . '"';
            if (strpos(file_get_contents($path), $type) != false && strpos(file_get_contents($path), $type) >= 0) {
                if ($forceAdd) {
                    file_put_contents($path, file_get_contents($path) . $type . '=' . $val . PHP_EOL);
                }
                file_put_contents($path, str_replace(
                    $type . '="' . env($type) . '"',
                    $type . '=' . $val,
                    file_get_contents($path)
                ));
            } else {
                file_put_contents($path, file_get_contents($path) . $type . '=' . $val . PHP_EOL);
            }
        }
    }

    /**
     * return service status text
     * example: Pending,Done/Completed,Processing by status
     */
    public static function serviceStatus($status)
    {
        $serviceStatus = ['Pending', 'Processing', 'Approved', 'Cancel', 'Completed'];
        return $serviceStatus[$status];
    }

    public static function isSiteInstalled()
    {
        $canInstall = new canInstall();
        if ((env('APP_NAME') != null && env('APP_NAME') != '' && env('APP_NAME') != 'demo') || $canInstall->alreadyInstalled())
            return true;
        return false;
    }
    public static function isEmailConfigured()
    {
        if (env('MAIL_MAILER') && env('MAIL_HOST') && env('MAIL_PASSWORD') && env('MAIL_USERNAME') && env('MAIL_PORT'))
            return true;
        return false;
    }

    public static function emptyToNull($val)
    {
        if ($val == null || $val == "")
            return null;
        else
            return $val;
    }
    public static function emptyOrNullToZero($val)
    {
        if ($val == null ||  $val == "")
            return 0;
        else
            return $val;
    }
}
