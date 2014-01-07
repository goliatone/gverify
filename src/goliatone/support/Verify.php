<?php namespace goliatone\support;

/**
 * TODO: Implement processors and filters as
 *       event handlers.
 * TODO: Move reporting logic to it's own
 *       class.
 * TODO: Implement use($assertionProvider) as
 *       a mixin. So we can dinamically add
 *       handlers.
 * TODO: Accept a callable $message
 *       that way we can implement GUnit
 *       assertions, we throw error on fail.
 * TODO: Figure out a way to provide a `bind`
 *       method. Right now, the instance provide
 *       is out of scope or something and does
 *       not contain the updated state, as
 *       pass by referecence vs value.
 */
class Verify
{

    public static $vtemplate;
    public static $vprocessors = array();

    public static function setTemplate($template)
    {
        self::$vtemplate = $template;
    }

    public static function that($label)
    {
        //TODO: We might want to hold this one
        //      as the root- empty- and pass $flow->next()
        $flow = new Verify($label);
        $flow->withTemplate(self::$vtemplate);
        return $flow;
    }

    public $template;
    public $label;
    public $status;
    public $message;
    public $result;


    protected $_filter = null;
    protected $_alias  = null;
    protected $_args   = null;
    //We would want to exclude this from context
    protected $messages = array();
    protected $processors = array();

    public function __construct($label)
    {
        $this->label = $label;

        $this->providing('status', function ($v) {
            return $v->status   = $v->result ? 'success' : 'fail';
        });
        $this->providing('message', function ($v) {
            return $v->message  = $v->messages[$v->status];
        });
    }

    public function next($label)
    {
        $flow = new Verify($label);
        $flow->withTemplate(self::$vtemplate);
        return $flow;
    }

    public function withTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    public function success($message)
    {
        //TODO: Accept a callable $message.
        $this->messages['success'] = $message;
        return $this;
    }

    public function fail($message)
    {
        // TODO: Accept a callable $message
        // that way we can implement GUnit
        // assertions, we throw error on fail.
        $this->messages['fail'] = $message;
        return $this;
    }

    public function __call($key, $args)
    {

        //TODO: Add filter($key, function);
        $this->_filter = in_array($key, array('and', 'or', 'not')) ? $key : null;
        //TODO: Add aliase($alias, $method) => WE could use a dispatcher mechanism.
        $aliases = array('is'=>'check',
            'and'=>'check',
            'or'=>'check',
            'not'=>'check',
            'then'=>'success',
            'else'=>'fail'
        );

        //keep track of current alias
        $this->_alias = array_key_exists($key, $aliases) ? $key : null;

        if (!array_key_exists($key, $aliases)) {
            throw new BadMethodCallException("Call to undefined method ".__CLASS__."::$key()");
        }

        //TODO: Store arguments, so we can ommit them in next checks.
        /*
        if($this->_alias && count($args) === 1)
        {
            $args = $this->_args;
        } else $this->_args = $args;
        */

        //Pick the real implementation, so
        //we can use reserved words is|and|or
        $method = $aliases[$key];

        return call_user_func_array(array($this, $method), $args);
    }



    public function check(callable $callback)
    {
        if (!is_callable($callback)) {
            throw new InvalidArgumentException("Provided invalid callback.");
        }

        $arguments = func_get_args();
        //get rid of our callback param
        /*
        echo "-----\nArguments are:\n";
        echo $this->_alias.PHP_EOL;
        print_r($arguments);
        */
        array_shift($arguments);
        /*
        echo "-----\nthen are:\n";
        print_r($arguments);
        echo "====\n";
        */
        $old = $this->result;
        $value =  call_user_func_array($callback, $arguments);

        if ($this->_filter) {
            $operator = "_".$this->_filter;
            //TODO: If we add filter support, make it a callable like providers
            $value = $this->{$operator}($old, $value);
            $this->_filter = null;
        }
        $this->_alias = null;

        $this->result = $value;

        return $this;
    }


    public function __toString()
    {
        return $this->compile();
    }

    public function __destruct()
    {
        //Cheap way to print without
        //making an explicit call, mainly
        //for CLI
        return $this->compile();
    }



    //TODO: do we want to rename to SET?
    public function providing($key, $value = null)
    {
        if(defined($key)) $value = constant($key);
        $this->processors[$key] = $value;
        return $this;
    }
    //TODO: Do we want to merge this into providing method?
    public function bind($key, $value = null, $global = null)
    {
        if(defined($key)) $value = constant($key);
        self::$vprocessors[$key] = $value;
        return $this;
    }

/////////////////////////////////////////
/// REPORTING
/// TODO: Move to either base class or
///       use composition. If we do
///       composition, then get_object_vars
///       would only get public properties
///       which is good.
/////////////////////////////////////////

    public function compile($echo = true)
    {

        $context  = $this->generateContext();

        $out =  $this->interpolate($this->template, $context);

        //We do a second pass to cover processors
        $out = $this->interpolate($out, $context);

        if ($echo === true) {
            echo $out.PHP_EOL;
        }

        return $this;
    }

    public function generateContext()
    {
        $properties = get_object_vars($this);
        $processors = array_replace_recursive(self::$vprocessors, $this->processors);

        // print_r($processors).PHP_EOL;
        // exit;

        foreach ($processors as $key => $value) {
            if(is_callable($value)) $properties[$key] = call_user_func($value, $this);
            else $properties[$key] = $value;
        }


        return $properties;
    }

    public function interpolate($message, array $context = array())
    {
        if (strpos($message, '{{') === false) {
            return $message;
        }

        $replace = array();
        foreach ($context as $key => $val) {
            $replace['{{' . $key . '}}'] = $val;
        }
        // interpolate replacement values into the the message and return
        //mb_strstr
        return @strtr($message, $replace);
    }
////////////////////////////////////////
/// BASIC FILTERS
/// TODO: Append from external provider
///       use(baseAssertionAndFilters)
////////////////////////////////////////

    public function _and($old, $value)
    {
        return $old && $value;
    }

    public function _or($old, $value)
    {
        return $old || $value;
    }

    public function _not($old, $value)
    {
        return !$value;
    }
}