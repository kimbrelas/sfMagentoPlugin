<?php

class testActions extends sfActions
{
  public function executeIndex(sfWebRequest $request)
  {
    $transport = new sfMagentoTransport('symfony', 'magento');
    
    $item = new SomeOtherItem();
    $item->setId(1);
    $item->setName('Failure.');
    
    $transport->persist($item);
    
    $item->setName('testing');
    
    $transport->persist($item);
    
    $this->data = $transport->flush();
  }
  
  public function executeSendMeData(sfWebRequest $request)
  {
    $data = unserialize($request->getParameter('data'));
    
    foreach($data as $key => $item)
    {
      $data[$key]['name'] = 'Success!';
    }
    
    $this->data = serialize($data);
    
    $this->setLayout(false);
  }
}