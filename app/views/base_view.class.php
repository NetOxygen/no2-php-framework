<?php
/**
 * Parent of all Views of this application.
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
class BaseView extends No2_View {
    /**
     * This method is overrided in order to setup some variables at the
     * template scope.
     *
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     *   Those locale variables are actually used by the template(s) file(s).
     */
    protected function _render($tpl_file) {
        global $router; // used for *_url() methods.
        global $view;   // used for render() method.
        include $tpl_file;
    }
}
