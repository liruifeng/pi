<?php
/**
 * Pi Engine (http://pialog.org)
 *
 * @link            http://code.pialog.org for the Pi Engine source repository
 * @copyright       Copyright (c) Pi Engine http://pialog.org
 * @license         http://pialog.org/license.txt New BSD License
 */

namespace Pi\Application\Registry;

use Pi;

/**
 * Event/Listener list
 *
 * Taiwen Jiang <taiwenjiang@tsinghua.org.cn>
 */
class Event extends AbstractRegistry
{
    /**
     * load event data from module config
     *
     * A module event configuration file (events in `module/press/config/event.ini.php`):
     *
     * <code>
     *  $event[] = 'article_post';
     *  $event[] = 'article_delete';
     *  $event[] = 'article_rate';
     * </code>
     *
     * Trigger in `module/press/controller/ArticleController.php`
     *
     * <code>
     *  Pi::service('event')->trigger('press_article_post', $articleObject);
     * </code>
     *
     * Callback configurations in `module/user/config/event.ini.php`
     *
     * <code>
     *  $observer['press'.']['article_post'][] = 'stats::article';
     * </code>
     *
     * Callback class in module/user/class/stats.php
     *
     * <code>
     * class UserStats
     * {
     *      public static function article($articleObject)
     *      {
     *      }
     * }
     * </code>
     */
    protected function loadDynamic($options)
    {
        $listeners = array();
        $modelEvent = Pi::model('event');
        $rowset = $modelEvent->select(array(
            'module'    => $options['module'],
            'name'      => $options['event'],
            'active'    => 1
        ));
        if ($rowset->count()) {
            return $listeners;
        }

        $modelListener = Pi::model('event_listener');
        $select = $modelListener->select()->where(array(
            'event_module'  => $options['module'],
            'event_name'    => $options['event'],
            'active'        => 1
        ));
        $listenerList = $modelListener->selectWith($select);
        $directory = Pi::service('module')->directory($options['module']);
        foreach ($listenerList as $row) {
            $class = sprintf('Module\\%s\\%s', ucfirst($directory), ucfirst($class));
            $listeners[] = array($class, $row->method, $row->module);
        }

        return $listeners;
    }

    /**
     * {@inheritDoc}
     * @param string    $module
     * @param string    $event
     */
    public function read($module, $event)
    {
        if (empty($event)) return false;
        $options = compact('module', 'event');
        return $this->loadData($options);
    }

    /**
     * {@inheritDoc}
     * @param string        $module
     * @param string|null   $event
     */
    public function create($module, $event = null)
    {
        $this->clear($module);
        $this->read($module, $event);
        return true;
    }
}
