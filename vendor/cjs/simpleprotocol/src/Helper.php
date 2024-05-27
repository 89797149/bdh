<?php
namespace CjsProtocol;

function toJson($ary) {
    return \json_encode($ary, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
}

function json_encode($data, $options=null) {
    if(is_null($options)) {
        $options = 0;
        if (defined('JSON_UNESCAPED_SLASHES')) {
            $options |= JSON_UNESCAPED_SLASHES;
        }
        if (defined('JSON_UNESCAPED_UNICODE')) {
            $options |= JSON_UNESCAPED_UNICODE;
        }
    }
    return \json_encode($data, $options);
}

function with($object) {
    return $object;
}
