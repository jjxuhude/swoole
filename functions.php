<?php
/**
 * Created by PhpStorm.
 * User: Jack.Xu1
 * Date: 2019/8/9
 * Time: 23:52
 */
function dump($data){

    $data=print_r($data,true);
    $html="<pre>$data</pre>";
    return $html;
}
