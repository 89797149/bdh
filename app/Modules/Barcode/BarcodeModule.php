<?php
/**
 * 条形码
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-07-05
 * Time: 9:33
 */

namespace App\Modules\Barcode;


use App\Models\BaseModel;

class BarcodeModule extends BaseModel
{
    /**
     * 生成条形码
     * @param string $str 条码值
     * @return string
     * */
    public function createBarcodeImg(string $str)
    {
        $root = $_SERVER['DOCUMENT_ROOT'] . '/ThinkPHP/Library/Vendor/';
        require_once $root . "/Picqer/Barcode/src/BarcodeGenerator.php";
        require_once $root . "/Picqer/Barcode/src/BarcodeGeneratorSVG.php";
        $generatorSVG = new \Picqer\Barcode\BarcodeGeneratorSVG();
        $barcode = $generatorSVG->getBarcode($str, $generatorSVG::TYPE_CODE_128);
        $res = 'data:image/svg+xml;base64,' . base64_encode($barcode);
        return $res;
    }
}