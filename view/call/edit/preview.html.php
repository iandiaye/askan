<?php

use Goteo\Core\View,
    Goteo\Library\Text,
    Goteo\Library\SuperForm,
    Goteo\Model;

$call = $this['call'];

$the_logo = empty($call->logo) ? 1 : $call->logo;
$call->logo = Model\Image::get($the_logo);
$the_image = empty($call->image) ? 1 : $call->image;
$call->image = Model\Image::get($the_image);

$call->categories = Model\Call\Category::getNames($call->id);
$call->icons = Model\Call\Icon::getNames($call->id);

$types   = $this['types'];
$errors = $call->errors ?: array();

$finishable = true;

// miramos el pruimer paso con errores para mandarlo a ese
$goto = 'view-step-userProfile';
foreach ($this['steps'] as $id => $data) {
    if (empty($step) && !empty($call->errors[$id])) {
        $goto = 'view-step-' . $id;
        $finishable = false;
        break;
    }
}

// boton de revisar que no sirve para mucho
$buttons = array(
    'review' => array(
        'type'  => 'submit',
        'name'  => $goto,
        'label' => Text::get('form-self_review-button'),
        'class' => 'retry'
    )
);

// si es enviable ponemos el boton
if ($finishable) {
    $buttons['finish'] = array(
        'type'  => 'submit',
        'name'  => 'finish',
        'label' => Text::get('form-send_review-button'),
        'class' => 'confirm red'
    );
} else {
    $buttons['nofinish'] = array(
        'type'  => 'submit',
        'name'  => 'nofinish',
        'label' => Text::get('form-send_review-button'),
        'class' => 'confirm disabled',
        'disabled' => 'disabled'
    );
}

// elementos generales de preview
$elements      = array(
    'process_preview' => array (
        'type' => 'hidden',
        'value' => 'preview'
    ),

    'splash' => array(
        'type'      => 'html',
        'class'     => 'fullwidth',
        'html'      =>  '<table><tr><td>'
                        . '<a href="/call/'.$call->id.'?preview=apply" class="button" target="_blank">'.Text::get('call-see_splash-button').' de aplicaci&oacute;n</a>'
                        . '</td><td>'
                        . '<a href="/call/'.$call->id.'/info?preview=apply" class="button" target="_blank">'.Text::get('call-see_main-button').' de aplicaci&oacute;n</a>'
                        . '</td></tr><tr><td>'
                        . '<a href="/call/'.$call->id.'?preview=campaign" class="button" target="_blank">'.Text::get('call-see_splash-button').' de campa&ntilde;a</a>'
                        . '</td><td>'
                        . '<a href="/call/'.$call->id.'/info?preview=campaign" class="button" target="_blank">'.Text::get('call-see_main-button').' de campa&ntilde;a</a>'
                        . '</td></tr><tr><td>'
                        . '<a href="/dashboard/calls" class="button">'.Text::get('call-go_dashboard-button').'</a>'
                        . '</td></tr></table>'
    )

/*
    'preview' => array(
        'type'      => 'html',
        'class'     => 'fullwidth',
        'html'      =>   '<div class="project-preview" style="position: relative"><div>'
                       . '<div class="overlay" style="position: absolute; left: 0; top: 0; right: 0; bottom: 0; z-index: 999"></div>'
                       . '<div style="z-index: 0">'
                       . '</div>'
                       . '</div></div>'
    )
 *
 */
);

// Footer
$elements['footer'] = array(
    'type'      => 'group',
    'children'  => array(
        'errors' => array(
            'title' => Text::get('form-footer-errors_title'),
            'view'  => new View('view/project/edit/errors.html.php', array(
                'project'   => $call,
                'step'      => $this['step']
            ))                    
        ),
        'buttons'  => array(
            'type'  => 'group',
            'children' => $buttons
        )
    )

);

// lanzamos el superform
echo new SuperForm(array(
    'action'        => '',
    'level'         => $this['level'],
    'method'        => 'post',
    'title'         => Text::get('preview-main-header'),
    'hint'          => Text::get('guide-call-preview'),
    'elements'      => $elements
));