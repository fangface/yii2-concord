<?php
/**
 * This file is part of the fangface/yii2-concord package
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 *
 * @package fangface/yii2-concord
 * @author Fangface <dev@fangface.net>
 * @copyright Copyright (c) 2014 Fangface <dev@fangface.net>
 * @license https://github.com/fangface/yii2-concord/blob/master/LICENSE.md MIT License
 *
 */

namespace fangface\widgets;

use backend\assets\CKEditorAsset;
use fangface\widgets\InputWidget;
use fangface\helpers\Html;
use yii\helpers\ArrayHelper;


/**
 * CKEditor widget
 */
class CKEditor extends InputWidget
{

    /**
     * @var string the name of the jQuery plugin
     */
    public $pluginName = 'ckeditor';
    /**
     * @var array default widget plugin options that user pluginOptions will be merged into
     */
    public $defaultPluginOptions = [];
    /**
     * @var array the HTML attributes for the input tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    public $options = ['class' => 'form-control ckeditor'];
    /**
     * @var atring the default pre-prepared config option to use [full, standard, basic or minimal]
     */
    public $preset = 'standard';

    /**
     * Renders the color picker widget
     */
    protected function renderWidget()
    {
        $this->prepareConfig();
        $this->prepareInput();
        $this->registerAssets();
        $this->prepareTemplate();
        echo $this->renderTemplate();
    }

    /**
     * Prepare config for the editor
     *
     * @return string
     */
    protected function prepareConfig()
    {
        $userOptions = $this->pluginOptions;
        $localOptions = [];
        switch ($this->preset) {

            case 'minimal':
                $localOptions = [
                    'height' => 100,
                    'toolbarGroups' => [
                        ['name' => 'undo'],
                        ['name' => 'basicstyles',
                            'groups' => ['basicstyles', 'cleanup'], //wlchere would like to add specialchar
                        ],
                        ['name' => 'clipboard'],
                    ],
                    'removeButtons' => 'Subscript,Superscript',
                    /*'removePlugins' => 'elementspath',*/
                    'resize_enabled' => false, // wlchere - not working
                ];
                break;

            case 'basic':
                $localOptions = [
                    'height' => 150,
                    'toolbarGroups' => [
                        ['name' => 'undo'],
                        ['name' => 'basicstyles',
                            'groups' => ['basicstyles', 'cleanup']
                        ],
                        ['name' => 'colors'],
                        ['name' => 'links',
                            'groups' => ['links', 'insert']
                        ],
                        ['name' => 'others',
                            'groups' => ['others', 'about']
                        ],
                    ],
                    'removeButtons' => 'Subscript,Superscript,Flash,Table,HorizontalRule,Smiley,SpecialChar,PageBreak,Iframe,Youtube',
                    /*'removePlugins' => 'elementspath',*/
                    'resize_enabled' => false,
                    'removeDialogTabs' => 'link:advanced',
                ];
                break;

            case 'standard':
            default:
                $localOptions = [
                    'height' => 200,
                    'toolbarGroups' => [
                        ['name' => 'clipboard',
                            'groups' => ['mode', 'undo', 'selection', 'clipboard', 'doctools']
                        ],
                        ['name' => 'editing',
                            'groups' => ['tools', 'about']
                        ],
                        '/',
                        ['name' => 'paragraph',
                            'groups' => ['templates', 'list', 'indent', 'align']
                        ],
                        ['name' => 'insert'],
                        '/',
                        ['name' => 'basicstyles',
                            'groups' => ['basicstyles', 'cleanup']
                        ],
                        ['name' => 'colors'],
                        ['name' => 'links'],
                        ['name' => 'others'],
                    ],
                    'removeButtons' => 'Smiley,Iframe'
                ];
                break;

            case 'full':
                $localOptions = [
                    'height' => 300,
                    'toolbarGroups' => [
                        ['name' => 'document',
                            'groups' => ['mode', 'document', 'doctools']
                        ],
                        ['name' => 'clipboard',
                            'groups' => ['clipboard', 'undo']
                        ],
                        ['name' => 'editing',
                            'groups' => ['find', 'selection', 'spellchecker']
                        ],
                        ['name' => 'forms'],
                        '/',
                        ['name' => 'basicstyles',
                            'groups' => ['basicstyles', 'colors','cleanup']
                        ],
                        ['name' => 'paragraph',
                            'groups' => [ 'list', 'indent', 'blocks', 'align', 'bidi' ]
                        ],
                        ['name' => 'links'],
                        ['name' => 'insert'],
                        '/',
                        ['name' => 'styles'],
                        ['name' => 'blocks'],
                        ['name' => 'colors'],
                        ['name' => 'tools'],
                        ['name' => 'others'],
                    ],
                ];
                break;

            case 'all':
                // default ckeditor internal config
                break;
        }

//wlchere
// console.plugins = 'dialogui,dialog,a11yhelp,about,basicstyles,bidi,blockquote,clipboard,button,panelbutton,panel,floatpanel,colorbutton,colordialog,menu,contextmenu,dialogadvtab,div,elementspath,enterkey,entities,popup,filebrowser,find,fakeobjects,flash,floatingspace,listblock,richcombo,font,format,forms,horizontalrule,htmlwriter,iframe,image,indent,indentblock,indentlist,justify,menubutton,language,link,list,liststyle,magicline,maximize,newpage,pagebreak,pastefromword,pastetext,preview,print,removeformat,resize,save,scayt,selectall,showblocks,showborders,smiley,sourcearea,specialchar,stylescombo,tab,table,tabletools,templates,toolbar,undo,wsc,wysiwygarea,autogrow,xml,ajax,lineutils,widget,codesnippet,codemirror,confighelper,maxheight,onchange,quicktable,stylesheetparser-fixed,tableresize,youtube';
// optional maxheight plugin
/*
plugins : {
        'a11yhelp' : 1,
        'about' : 1,
        'ajax' : 1,
        'autogrow' : 1,
        'basicstyles' : 1,
        'bidi' : 1,
        'blockquote' : 1,
        'clipboard' : 1,
        'codemirror' : 1,
        'codesnippet' : 1,
        'colorbutton' : 1,
        'colordialog' : 1,
        'confighelper' : 1,
        'contextmenu' : 1,
        'dialogadvtab' : 1,
        'div' : 1,
        'elementspath' : 1,
        'enterkey' : 1,
        'entities' : 1,
        'filebrowser' : 1,
        'find' : 1,
        'flash' : 1,
        'floatingspace' : 1,
        'font' : 1,
        'format' : 1,
        'forms' : 1,
        'horizontalrule' : 1,
        'htmlwriter' : 1,
        'iframe' : 1,
        'image' : 1,
        'indentblock' : 1,
        'indentlist' : 1,
        'justify' : 1,
        'language' : 1,
        'link' : 1,
        'list' : 1,
        'liststyle' : 1,
        'magicline' : 1,
        'maxheight' : 1,
        'maximize' : 1,
        'newpage' : 1,
        'onchange' : 1,
        'pagebreak' : 1,
        'pastefromword' : 1,
        'pastetext' : 1,
        'preview' : 1,
        'print' : 1,
        'quicktable' : 1,
        'removeformat' : 1,
        'resize' : 1,
        'save' : 1,
        'scayt' : 1,
        'selectall' : 1,
        'showblocks' : 1,
        'showborders' : 1,
        'smiley' : 1,
        'sourcearea' : 1,
        'specialchar' : 1,
        'stylescombo' : 1,
        'stylesheetparser-fixed' : 1,
        'tab' : 1,
        'table' : 1,
        'tableresize' : 1,
        'tabletools' : 1,
        'templates' : 1,
        'toolbar' : 1,
        'undo' : 1,
        'wsc' : 1,
        'wysiwygarea' : 1,
        'youtube
 */
        $this->pluginOptions = ArrayHelper::merge($localOptions, $userOptions);
    }

    /**
     * Prepare the input fields for the input
     *
     * @return void
     */
    protected function prepareInput()
    {
        if ($this->hasModel()) {
            $this->sections['input'] = Html::activeTextarea($this->model, $this->attribute, $this->options);
        } else {
            $this->sections['input'] = Html::textarea($this->name, $this->value, $this->options);
        }
    }

    /**
     * Registers the needed client assets
     *
     * @return void
     */
    public function registerAssets()
    {
        if ($this->disabled) {
            return;
        }
        $view = $this->getView();
        CKEditorAsset::register($view);
        $element = "jQuery('#" . $this->options['id'] . "')";
        $this->registerPlugin($this->pluginName, $element);
    }

}
