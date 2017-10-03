<?php
/**
 * Copyright (c) 2016 - 2017 by Adam Banaszkiewicz
 *
 * @license   MIT License
 * @copyright Copyright (c) 2016 - 2017, Adam Banaszkiewicz
 * @link      https://github.com/requtize/assetter
 */
namespace Requtize\Assetter\Plugin;

use Leafo\ScssPhp\Compiler;
use Leafo\ScssPhp\Formatter\Crunched;
use Requtize\Assetter\Assetter;
use Requtize\Assetter\PluginInterface;

class LeafoScssPhpPlugin implements PluginInterface
{
    protected $filesRoot;
    protected $freshFile;

    public function __construct($filesRoot)
    {
        $this->filesRoot = $filesRoot;
        $this->scss = new Compiler;
        $this->scss->setFormatter(Crunched::class);
    }

    public function register(Assetter $assetter)
    {
        $this->freshFile = $assetter->getFreshFile();

        $assetter->listenEvent('load.all', [ $this, 'replaceAndCompile' ]);
        $assetter->listenEvent('load.css', [ $this, 'replaceAndCompile' ]);
    }

    public function replaceAndCompile(array & $groups)
    {
        foreach($groups as $kg => $group)
        {
            foreach($group['files'] as $key => $file)
            {
                if(substr($file['file'], -5, 5) === '.scss')
                {
                    $groups[$kg]['files'][$key]['file']     = $this->compile($file['file']);
                    $groups[$kg]['files'][$key]['revision'] = $this->freshFile->getFilemtimeMetadata($this->filesRoot.$file['file']);
                }
            }
        }
    }

    public function compile($filepath)
    {
        $filepathRoot = $this->filesRoot.$filepath;
        $filepathNew  = str_replace('.scss', '.css', $filepath);

        if($this->freshFile->isFresh($filepathRoot) === false)
        {
            $css = $this->scss->compile(file_get_contents($filepathRoot));

            file_put_contents($this->filesRoot.$filepathNew, $css);
        }

        return $filepathNew;
    }
}
