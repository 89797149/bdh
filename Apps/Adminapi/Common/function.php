<?php

    function getimgsrc($url){ 
        if(strstr($url,'http://') || strstr($url,'https://')){
            return $url;
        }

        if(strstr($url,'qiniu://')){
           $url = str_replace("qiniu://", $GLOBALS['CONFIG']['qiniuDomain'],$url);
        }else{
            return __ROOT__. '/' . $url;
        }

        return $url;
    }

    