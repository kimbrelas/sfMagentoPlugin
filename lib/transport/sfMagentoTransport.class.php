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
    
    $data[spl_object_hash($obj)] = $obj;
    
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
    if(!$this->data)
    {
      return array();
    }
    
    $data = $this->getNormalizedArray();
    
    // curl to the specified url and return the resulting data
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $this->url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, 'data='.$data);
    
    $newData = $this->updateDataFromNormalizedArray(curl_exec($curl));
    
    $this->setData($newData);
    
    return $this->getData();
  }
  
  /**
   * translating any objects in the data to a normalized array
   */
  public function getNormalizedArray()
  {
    $normalized = array();
    
    foreach($this->getData() as $key => $obj)
    {
      // add the object's key with empty array value
      $normalized[$key] = array();
      
      $reflectionClass = new ReflectionClass(get_class($obj));
      
      // populate each one of the object's properties
      foreach($reflectionClass->getProperties() as $property)
      {
        $getValue = 'get'.ucfirst($property->name);
        
        $normalized[$key][$property->name] = $obj->$getValue();
      }
    }
    
    return $normalized;
  }
  
  /**
   * merge the normalized arrays back into the data objects
   */
  public function updateDataFromNormalizedArray($array)
  {
  }
}