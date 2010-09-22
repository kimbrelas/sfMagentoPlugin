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
    $transport = new sfMagentoTransport('magento', 'symfony');
    $transport->setData($request->getParameter('data'));
    
    $data = $transport->getData();
    
    foreach($data as $key => $item)
    {
      $data[$key]['name'] = 'Success!';
    }
    
    $transport->setData($data);
    
    $this->data = serialize($transport->getData());
    
    $this->setLayout(false);
  }
}