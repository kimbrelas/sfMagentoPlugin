<?php

class sfMagentoTransport
{
  // serialized array - data to be sent to foreign framework
  protected $data;
  
  // string - url we will be curling this data to
  protected $url;
  
  /**
   * configure the transport object
   */
  public function __construct($from, $to)
  {
    //$this->url = sfMagentoConfig::get('someconfigvar');
    $this->url = 'http://lah.localhost/magento/data';
  }
  
  /**
   * persist an object to the transport object
   */
  public function persist($obj)
  {
    $data = $this->getData();
    
    $data[get_class($obj).'Collection'][] = $obj;
    
    $this->setData($data);
  }
  
  /**
   * manually set the data for the transport object
   */
  public function setData($data)
  {
    $this->data = (is_array($data)) ? serialize($data) : $data;
  }
  
  /**
   * get the data from the transport object
   */
  public function getData()
  {
    return ($this->data) ? unserialize($this->data) : array();
  }
  
  /**
   * if there is data on the transport object, curl
   */
  public function flush()
  {
    return ($this->data) ? $this->curl() : $this->getData();
  }
  
  /**
   * post to the specified url and return the resulting data
   */
  protected function curl()
  {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $this->url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, 'data='.$this->data);
    
    $this->setData(curl_exec($curl));
    
    return $this->getData();
  }
}