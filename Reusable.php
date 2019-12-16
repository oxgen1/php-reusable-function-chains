class Reusable {
    use Treatable;
    protected $treatment = [];
    public function registerTreatment($name, $parameters = []){
        if (static::hasTreatment($name) || method_exists($this, $name)){
            $this->treatment[$name] = $parameters;
        }
    }
    public function __invoke($doPass = false, $passed) {
       foreach($this->treatment as $method => $params){
            if($doPass && isset($passed)){
                array_unshift($params, $passed);
            }
           $passed = call_user_func_array(static::$treatments[$method], $params);
       }
       return $passed;
    }
}
trait Treatable {
    protected static $treatments = [];
    public static function treatment($name, $function){
        static::$treatments[$name] = $function;
    }
    
    public static function hasTreatment($name){
        return isset(static::$treatments[$name]);
    }   
    public function __call($method, $parameters) {
        echo 'Call was Called';
        if(! static::hasTreatment($method)){
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }
        $treatment = static::$treatments[$method];
        if ($treatment instanceof Closure) {
            return call_user_func_array(Closure::bind($treatment, null, static::class), $parameters);
        }
        return  call_user_func_array($treatment, $parameters);
    }
    public static function __callStatic($method, $parameters) {
        if(! static::hasTreatment($method)){
            throw new BadMethodCallException(sprintf(
                'Method %s::%s does not exist.', static::class, $method
            ));
        }
        $treatment = static::$treatments[$method];
        if ($treatment instanceof Closure) {
            return call_user_func_array($treatment->bindTo($this, static::class), $parameters);
        }
        return  call_user_func_array($treatment, $parameters);
    }
}
$treat = new Reusable();
$treat::treatment('one', function($a, $b){
    array_push($a, $b);
    return $a;
});
$treat::treatment('two', function($a){
    array_push($a, 'test2');
    return $a;
});
$treat::treatment('three', function($a){
    array_push($a, 'test3');
    return $a;
});
$treat->registerTreatment('one', ['mess']);
$treat->registerTreatment('two', []);
$treat->registerTreatment('three', []);
echo "\n"; 
print_r($treat(true, array()));
