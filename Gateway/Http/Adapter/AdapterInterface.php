<?php
namespace Veriteworks\Paypal\Gateway\Http\Adapter;

interface AdapterInterface
{
    /**
     * @param $path
     * @return mixed
     */
    public function setApiPath($path);
    /**
     * @param array $param
     * @return mixed
     */
    public function execute(array $param);

    /**
     * @param $storeId
     * @return mixed
     */
    public function setStoreId($storeId);
}
