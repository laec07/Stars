<?php

namespace App\Observers;
use App\Models\FormFisios\Ficha;
use App\Models\FormFisios\FichaFicha;
use Illuminate\Support\Facades\Storage;
class FichaObserver
{
     public function deleting(Ficha $ficha)
    {
        if ($ficha->nota_detallada) {

            preg_match_all(
                '/<img[^>]+src="([^">]+)"/',
                $ficha->nota_detallada,
                $matches
            );

            foreach ($matches[1] as $url) {

                $path = str_replace(asset('storage') . '/', '', $url);

                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }
    }
}