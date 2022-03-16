<?php
namespace Veriteworks\Paypal\Api;

interface PostManagementInterface
{
    /**
     * @param mixed $param
     * @return string
     */
    public function getTransId($param);

    /**
     * @param mixed $param
     * @return array
     */
    public function authorize($param);

    /**
     * @param mixed $param
     * @return array
     */
    public function capture($param);

    /**
     * @param mixed $param
     * @return array
     */
    public function processError($param);
}
