<?php

namespace Picqer\Barcode;

class BarcodeBar
{
    protected $width;
    protected $height;
    protected $positionVertical;
    protected $type;

    const TYPE_BAR = 1;
    const TYPE_SPACING = 0;

    public function __construct(int $width, int $height, $drawBar = true, $positionVertical = 0)
    {
        $this->width = $width;
        $this->height = $height;
        $this->positionVertical = $positionVertical;
        $this->type = $drawBar ? self::TYPE_BAR : self::TYPE_SPACING;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getPositionVertical()
    {
        return $this->positionVertical;
    }

    public function isBar()
    {
        return $this->type === self::TYPE_BAR;
    }
}