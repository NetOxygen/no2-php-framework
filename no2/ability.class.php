<?php
/**
 * Authorization mechanism, heavily based on CanCan
 * (see https://github.com/CanCanCommunity/cancancan)
 *
 * @author
 *   Alexandre Perrin <alexandre.perrin@netoxygen.ch>
 */
abstract class No2_AbstractAbility
{
    /**
     * The owner of this ability.
     */
    protected $user;

    /**
     * @param $user
     *   The ability owner.
     */
    public function __construct($user)
    {
        $this->user = $user;
        $this->initialize();
    }

    /**
     * Declare authorization by calling allow with actions, resource and
     * conditions.
     *
     * This implementation only clear the authorization rules. Subclasses are
     * expected to override this method (and call parent::initialize() before
     * allow()).
     *
     * see allow()
     */
    protected function initialize()
    {
        $this->rules = [];
    }

    /**
     * helper to register an ability.
     *
     * For arguments, see the notes and usage examples.
     *
     * @throw InvalidArgumentException
     *
     * @note
     *   * an allow() call with multiple actions is equivalent to one allow()
     *     call for each action (with the same conditions arguments).
     *   * there can be only one call to allow() for the same [action,resource]
     *     couple.
     *   * multiple conditions can be given, all must evaluate to true in order
     *     to authorize.
     *   * array conditions can only be given with a resource and will be only
     *     evaluated on object.
     *   * function conditions with a resource will be only evaluated on object.
     *
     * <b>Example</b>
     * @code
     *   // start by calling the parent
     *   parent::initialize();
     *
     *   // always allow
     *   $this->allow('allowed-for-all');
     *
     *   // never allow
     *   $this->allow('disallowed-for-all', false);
     *
     *   // allow on some known condition at this time...
     *   if ($this->user->is_anonymous())
     *       $this->allow('login');
     *
     *   // ...also possible with the condition as argument
     *   $this->allow('logout', !$this->authorize('login'));
     *
     *   // function should be used if the condition is unknown right now.
     *   $this->allow('random', function () { return rand(); });
     *
     *   // Foo resource (a class) examples
     *
     *   // will allow when called with a Foo instance *or* the 'Foo' class
     *   // itself.
     *   $this->allow(['create'], 'Foo');
     *
     *   // array conditions only works for Foo instances
     *   $this->allow(['read', 'update'], 'Foo', ['created_by' => $this->user->id]);
     *
     *   // function conditions only works for Foo instances
     *   $this->allow('do-nasty-stuff', 'Foo', function ($foo) {
     *       return $foo->is_not_too_dirty() && $this->user->is_admin();
     *   });
     *
     *   // combination can bed used, all conditions must pass.
     *   $this->allow(['destroy'], 'Foo',
     *       $this->user->is_admin(),
     *       ['created_by' => $this->user->id],
     *       function ($foo) { return !$foo->is_indestructible(); }
     *   );
     * @endcode
     */
    protected function allow(/* ... */)
    {
        $argv = func_get_args();

        if (count($argv) < 1)
            throw new InvalidArgumentException('allow expect at least one argument');

        $actions = array_shift($argv); // first arg is always actions.
        if (!is_array($actions))
            $actions = [$actions];

        $resource = '';
        if (!empty($argv) && is_string($argv[0]))
            $resource = array_shift($argv);

        $conditions = $argv; // what is left are conditions

        foreach ($actions as $action)
            $this->register($action, $resource, $conditions);
    }

    /**
     * The rules array, filled by register() and used by authorize().
     *
     * <b>Example</b>
     * @code
     *   [
     *     'action'       => ['resource' => [...], 'other-resource' => [...]]
     *     'other-action' => ['resource' => [...], 'other-resource' => [...]]
     *   ]
     * @endcode
     */
    protected $rules = [ ];

    /**
     * register an authorization.
     *
     * @param $action
     *   an action string, usually a verb like 'edit', 'destroy', 'login' etc.
     *
     * @param $resource
     *   a resource as a string (usually a class description). The empty string
     *   can be used and means "no resource" (for actions like 'login' etc.)
     *
     * @param $conditions
     *   an array of conditions. if the array is empty it is considered that
     *   all conditions passed as if true was given (the authorization is
     *   successful).
     *
     * @throw InvalidArgumentException
     *   When a rule has already been registered for the given
     *   ($action, $resource) couple.
     */
    protected function register($action, $resource, $conditions)
    {
        /*
         * Basic conditions optimization: we can already evaluate the boolean
         * conditions right now, only register the context-dependent ones.
         */
        $cds = [];
        foreach ($conditions as $condition) {
            if (is_bool($condition)) {
                if (!$condition)
                    return;
            } else // not trivial
                $cds[] = $condition;
        }

        /*
         * make it so the empty array is equivalent to true
         */
        if (count($cds) === 0)
            $cds = true;

        if (!array_key_exists($action, $this->rules))
            $this->rules[$action] = [];

        if (array_key_exists($resource, $this->rules[$action])) {
            // a rule has already been defined for this action,resource couple
            throw new InvalidArgumentException(
                "double authorization rules for (action=$action,resource=$resource)"
            );
        }

        $this->rules[$action][$resource] = $cds;
    }

    /**
     * check if the ability owner can perform a given action on some resource.
     *
     * @param $action
     *   The action key to check.
     *
     * @param $target
     *   Some resource. Its meaning depends on the given action.
     *
     * @return
     *   true if the ability is authorized, false otherwise.
     */
    public function authorize($action, $target = '')
    {
        $authorized = false;
        $resource   = (is_object($target) ? get_class($target) : $target);

        if (
            array_key_exists($action, $this->rules) &&
            array_key_exists($resource, $this->rules[$action])
        ) {
            $conditions = $this->rules[$action][$resource];
            if (is_bool($conditions))
                return $conditions;
            $authorized = true;
            foreach ($conditions as $condition) {
                $authorized = $authorized &&
                    $this->eval_condition($condition, $target);
            }
        }

        return $authorized;
    }

    /**
     * helper for authorize, evaluate a given condition.
     *
     * @param $condition
     *   the condition to evaluate.
     *
     * @param $target
     *   the object or class on which the condition is evaluated.
     *
     * @return
     *   true on success, false otherwise.
     */
    protected function eval_condition($condition, $target)
    {
        if (is_bool($condition)) { // a very simple case
            return $condition;
        } elseif (is_array($condition) && is_object($target)) {
            // $condition is like ['created_by' => $id], we check that all
            // requested properties of $target match the expected values.
            $success = true;
            foreach ($condition as $key => $val)
                $success = ($success && $target->$key === $val);
            return $success;
        } elseif (is_callable($condition) && is_object($target)) {
            // $condition is a function taking a $target argument, simply
            // return the function's result normalized as a boolean.
            return ($condition($target) ? true : false);
        }
    }
}
