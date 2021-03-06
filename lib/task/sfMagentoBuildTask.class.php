<?php

class sfMagentoBuildModelTask extends sfBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', true),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
    ));

    $this->namespace = 'sfMagento';
    $this->name = 'build-model';
    $this->briefDescription = 'Creates classes for the current model';

    $this->detailedDescription = <<<EOF
The [sfMagento:build-model|INFO] task creates model classes from the schema:

  [./symfony sfMagento:build-model|INFO]

The task read the schema information in [config/sfMagento/schema.yml|COMMENT]
from the project and all enabled plugins.

The model classes files are created in [lib/model/sfMagento|COMMENT].
EOF;
  }
  
  protected function prepareSchemaFile($yamlSchemaPath)
  {
    $models = array();
    $finder = sfFinder::type('file')->name('schema.yml')->sort_by_name()->follow_link();

    // plugin models
    foreach ($this->configuration->getPlugins() as $name)
    {
      $plugin = $this->configuration->getPluginConfiguration($name);
      foreach ($finder->in($plugin->getRootDir().'/config/sfMagento') as $schema)
      {
        $pluginModels = (array) sfYaml::load($schema);

        foreach ($pluginModels as $model => $definition)
        {
          // merge this model into the schema
          $models[$model] = isset($models[$model]) ? sfToolkit::arrayDeepMerge($models[$model], $definition) : $definition;

          // the first plugin to define this model gets the package
          if (!isset($models[$model]['package']))
          {
            $models[$model]['package'] = $plugin->getName().'.lib.model.sfMagento';
          }

          if (!isset($models[$model]['package_custom_path']) && 0 === strpos($models[$model]['package'], $plugin->getName()))
          {
            $models[$model]['package_custom_path'] = $plugin->getRootDir().'/lib/model/sfMagento';
          }
        }
      }
    }

    // project models
    foreach ($finder->in($yamlSchemaPath) as $schema)
    {
      $projectModels = (array) sfYaml::load($schema);

      foreach ($projectModels as $model => $definition)
      {
        // merge this model into the schema
        $models[$model] = isset($models[$model]) ? sfToolkit::arrayDeepMerge($models[$model], $definition) : $definition;
      }
    }

    // create one consolidated schema file
    $file = realpath(sys_get_temp_dir()).'/sfMagento_schema_'.rand(11111, 99999).'.yml';
    $this->logSection('file+', $file);
    file_put_contents($file, sfYaml::dump($models, 4));

    return $file;
  }
  
  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $this->logSection('sfMagento', 'generating model classes');
    
    $config['models_path'] = sfConfig::get('sf_lib_dir').'/model/sfMagento';
    $config['yml_path']    = sfConfig::get('sf_config_dir').'/sfMagento';

    $schema = $this->prepareSchemaFile($config['yml_path']);
    
    $this->generateBaseFiles($schema, $config);
    $this->generateModelFiles($schema, $config);
    
    $this->reloadAutoload();
  }
  
  protected function generateBaseFiles($schema, $config)
  {
    $models_path = $config['models_path'];
    $yml_path    = $config['yml_path'];
    $base_dir    = $models_path.'/base';
    
    $base_finder = sfFinder::type('file')->name('Base*')->sort_by_name()->follow_link();
    
    // if the model/sfMagento dir doesnt exist, make it now
    if(!file_exists($models_path))
    {
      $this->logSection('dir+', $models_path);
      mkdir($models_path);
    }
    
    if(!file_exists($base_dir))
    {
      $this->logSection('dir+', $base_dir);
      mkdir($base_dir);
    }
    
    // clear out old base files
    foreach($base_finder->in($base_dir) as $base_file)
    {
      $this->logSection('file-', $base_file);
      unlink($base_file);
    }
    
    foreach (sfYaml::load($schema) as $model => $definition)
    {
      // if the package dir doesnt exist, make it now
      if(isset($definition['package']))
      {
        $package_dir = $models_path.'/'.substr($definition['package'], 0, strpos($definition['package'], '.'));
        $package_base_dir = $package_dir.'/base';
        
        if(!file_exists($package_dir))
        {
          $this->logSection('dir+', $package_dir);
          mkdir($package_dir);
        }
        
        if(!file_exists($package_base_dir))
        {
          $this->logSection('dir+', $package_base_dir);
          mkdir($package_base_dir);
        }
        
        // clear out old base files for this package
        foreach($base_finder->in($package_base_dir) as $pkg_base_file)
        {
          $this->logSection('file-', $pkg_base_file);
          unlink($pkg_base_file);
        }
      }
      
      $file = sprintf('%s/Base%s.class.php', isset($definition['package']) ? $package_base_dir : $base_dir, $model);
      
      // create the base file
      $this->logSection('file+', $file);
      $newfile = fopen($file, "w");
      fclose($newfile);
      
      $properties = array();
      $functions = array();
      foreach($definition['columns'] as $key => $type)
      {
        $properties[] = sprintf("// %s\n  ".'protected $%s;', $type, $key);
  
        $functions[] = sprintf(
  'public function set%s($%s)
  {
    $this->%s = $%s;
  }
  
  public function get%s()
  {
    return $this->%s;
  }', ucfirst($key), $key, $key, $key, ucfirst($key), $key);
      }
      
      $code = sprintf(
'<?php

class Base%s
{
  %s
  
  %s
}', 
        $model,
        implode("\n\n  ", $properties),
        implode("\n\n  ", $functions)
      );
      
      file_put_contents($file, $code);
    }
  }
  
  protected function generateModelFiles($schema, $config)
  {
    $models_path = $config['models_path'];
    $yml_path    = $config['yml_path'];

    foreach (sfYaml::load($schema) as $model => $definition)
    {
      if(isset($definition['package']))
      {
        $package_dir = $models_path.'/'.substr($definition['package'], 0, strpos($definition['package'], '.'));
      }
      
      $file = sprintf('%s/%s.class.php', isset($definition['package']) ? $package_dir : $models_path, $model);
      
      // create the model file if it doesn't already exist
      if(!file_exists($file))
      {
        $this->logSection('file+', $file);
        $newfile = fopen($file, "w");
        fclose($newfile);
        
        $code = sprintf(
'<?php

class %s extends Base%s
{
}', 
          $model,
          $model
        );
        
        file_put_contents($file, $code);
      }
    }
  }
}
