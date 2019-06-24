<?php
    class Relationship
    {
        public $To;
        public $Type;
        public $RetrievalQuery;
        public function Relationship($To)
        {
            $this->To = $To;
        }
    }
    class ManyRelation extends Relationship
    {
        public $Values = [];// many so we get back all values// can also add and remove from a many relation//
        public function ManyRelation($To)
        {
            parent::__construct($To);
        }
    }
    class OneRelation extends Relationship
    {
        public $Value;
        public function OneRelation($To)
        {
            parent::__construct($To);
        }
    }
    class RouteData
	{
		public $value;
		public function RouteData()
		{
			$this->value = $value;
		}
	}
    class NullData
	{
		public $value;
		public function NullData($value = null)
		{
			$this->value = $value;
		}
        public function ifNull($use)
        {
            if(is_null($this->value))$this->value = $use;
        }
	}
    class View
    {
        public $Master;// variable to pass to the master//
        public $Partial;// variables passed to the partial//
        public function View($Partial=null,$Master=null)
        {
            $this->Master = $Master;
            $this->Partial = $Partial;
        }
    }
    class JsonView
    {
        public $object;
        public function JsonView($object)
        {
            $this->object = $object;
        }
    }
    class Location
    {
        public $Controller;
        public $Route;
        public $Option;
        public function Location($Controller,$Route=null,$Option=[])
        {
            $this->Option = $Option;
            $this->Route = $Route;
            $this->Controller = $Controller;
        }
    }
    class Simple
    {
        public $Write;
        public function Simple($Write)
        {
            $this->Write = $Write;
        }
        public function Draw()
        {
            print $this->Write;
        }

    }
    class Convert
    {
        public static $Types = [
            "integer"=>"int(100)",
            "string" =>"varchar(255)",
            "boolean"=>"tinyint(2)",
            "double"=>"double",
            "DateTime"=>"datetime(6)"
        ];
        public static function GetType($item)
        {
           $t = gettype($item);
           return ($t == "object")?get_class($item):$t;
        }
        // public static function StringtoProperty($item,$string)
        // {
        //     $type = Convert::GetType($item);
        //     return Convert::StringtoType($type,$string);
        // }
        public static function StringtoType($type,$string)
        {
            if($type == "boolean")return boolval($string);
            if($type == "integer")return intval($string);
            if($type == "DateTime")return DateTime::createFromFormat('Y-m-d H:i', $string);
            if($type == "double")return doubleval($string);
            // if($type == "OneRelation")
            return $string;
        }
        public static function ClasstoTable($val)
        {
            $type = Convert::GetType($val);
            if(array_key_exists($type,Convert::$Types))
            {
                return ["Type"=>Convert::$Types[$type],"Value"=>Convert::toString($val,$type)];
            }
            return null;
        }
        public static function toString($val,$type = null)
        {
            $type = is_null($type) ? gettype($val):$type;
            $type = $type == "object" ? get_class($val):$type;
            if($type=="string")return addslashes($val);
            if($type == "boolean") return ($val)?1:0;
            if($type == "DateTime") return $val->format('Y-m-d H:i');
            if($type == "double" || $type = "integer") return gettext($val); 
            return null;
        }
    }
    class Configuration
    {
        public $DBaddress = "localhost";
        public $DBname = "";
        public $DBuser = "";
        public $DBpass = "";
        public $ModelPath = "Models";
        public $TemplatePath = "Templates";
        public $ComponentSheet = "Templates/Components.include.php";
        public $ControllerPath = "Controllers";
        public $ScriptsPath = "Scripts";
        public $DefaultControl = "Main";// name of the default class//
        public $errorPage = "Error";// name the function on class//
        public $devNode = "Dev";
        public function Save($filename = "Config.include.php")// save it bitch
        {
            $r = new ReflectionClass("Configuration");
            $p = $r->getProperties();
            $str = "<?php\n";
            $str .= "\t\$Configure = [";
            $arr = [];
            foreach ($p as $prop)
                $arr[] = "\n\t\t\"".$prop->name."\" => \"".$prop->getValue($this)."\"";
            $str.= implode(",",$arr)."\n\t];\n";
            $str.="?>";
            $f = fopen($filename,"w+");
            fwrite($f,$str);
            fclose($f);
        }
        public static function Load($filename = "Config.include.php")// loads a copy of configuration
        {
            if(file_exists($filename))
            {
                $tmp = new Configuration();
                include $filename;
                $r = new ReflectionClass("Configuration");
                if(isset($Configure))
                {
                    foreach ($Configure as $Name => $Value) {
                        if($r->hasProperty($Name))$r->getProperty($Name)->setValue($tmp,$Value);
                    }
                }
                return $tmp;
            }
            return null;
        }
    }
    class ComponentLibrary
    {   
        public $Components =[];
        public function ComponentLibrary()
        {
            $this->Components =
            [
                "DateTime" => ["Code"=>"<input type=\"date\" class=\"form-control\" name=\"{Name}\" value=\"{Value}\"><input type=\"time\" class=\"form-control\" name=\"{Name}\" value=\"{Value}\">","Type"=>"DateTime","Pattern"=>"{0} {1}","Required"=>true],
                "integer" => ["Code"=>"<input type=\"number\" class=\"form-control\" name=\"{Name}\" value=\"{Value}\">","Type"=>"integer"],
                "double" => ["Code"=>"<input type=\"number\" class=\"form-control\" name=\"{Name}\" value=\"{Value}\">","Type"=>"double"],
                "string" => ["Code"=>"<input type=\"text\" class=\"form-control\" name=\"{Name}\" value=\"{Value}\">","Type"=>"string"],
                "boolean" => ["Code"=>"<input type=\"checkbox\" class=\"form-control\" name=\"{Name}\" value=\"{Value}\">","Type"=>"boolean"]
            ];
        }
        public function StitchFromValues($Values)// unrecognised values will be passed back in the return, untouched// if model is given, it will attempt to attach the values to the model, if nkey is also given it will attempt to convert the given names to //
        {
            $v = [];
            $ret=[];
            foreach ($Values as $key => $value) {
                if(preg_match("/-[0-9]+/",$key))
                    $key = preg_replace("/-[0-9]+/","",$key);
                if(array_key_exists($key,$v))array_push($v[$key],$value); else $v[$key]=[$value];
            }
            foreach ($v as $key => $value) {
                $spl = explode("-",$key);
                if(count($spl) > 1)
                   $ret[$spl[0]] = $this->Stitch($spl[1],$value);
                else
                    $ret[$spl[0]] = $value[0];
            }
            return $ret;
        }

        public function Stitch($id,$Values)// stitch values together from a stitch pattern//
        {   
            $item = (array_key_exists($id,$this->Components))?$this->Components[$id]:null;
            if($item && isset($item["Pattern"]))
            {
                $a = [];
                foreach (explode(",",$item["Pattern"]) as $v) {
                    preg_match_all('/{([0-9]+)}/', $v, $matches, PREG_SET_ORDER, 0);
                    if($matches)
                    {
                        $matches = array_map(function($f){return $f[1];},$matches);
                        $s = preg_split('/{([0-9]+)}/',$v);
                        $bl = [];
                        foreach ($matches as $match) {
                            if(is_array($Values)){
                                if(array_key_exists($match,$Values)){array_push($bl,$Values[$match]);}
                            }else{
                                array_push($bl,$Values);
                            }
                        } 
                        array_push($a,array_shift($s) . implode(array_map(function($a,$b){return $a.$b;},$bl,$s)));
                    }
                }
                return count($a) > 1? $a : $a[0];
            }
            return is_array($Values) ? implode('',$Values) : $Values; 
        }

        public function Tear($id,$Values)// pull a string apart based on the stitch pattern then apply to component//
        {
            $item = (array_key_exists($id,$this->Components))?$this->Components[$id]:null;
            $Values = is_array($Values)?$Values:[$Values];
            $array=[];
            foreach ($Values as $string) {
                if(array_key_exists("Pattern",$item))
                {
                    $rep = preg_replace("/{([0-9]+)}/", "(.+)", $item["Pattern"]);
                    preg_match("/$rep/",$string,$v);
                    if($v)
                    {
                        array_shift($v);
                        $array = array_merge($array,$v);
                    }else
                    {
                        array_push($array,$string);
                    }
                }
                else
                {
                        array_push($array,$string);
                }
            }
            $array = count($array) == 1 ? $array[0] : $array;
            $array = count($array) > 0 ? $array :null;
            return $array;
        }
        
        public function Draw($id,$name,$value,$option=[])
        {
            $type = Convert::GetType($value);
            $item = (array_key_exists($id,$this->Components))?$this->Components[$id]:null;
            if(!$item)return null;
            if((array_key_exists("Required",$item) && array_key_exists("Type",$item) && $type == $item["Type"]) || (!array_key_exists("Required",$item)))
            {
                $value = ($type != "string" && $type != "array")? Convert::toString($value):$value;
                $option["Name"] = $name;
                $option["Value"] = array_key_exists("Pattern",$item) ? $this->Tear($id,$value) : $value;
                preg_match_all('/{([A-z]+)}/', $item["Code"], $matches, PREG_SET_ORDER, 0);
                if($matches)
                {
                    $matches = array_map(function($f){return trim($f[1],"\"");},$matches);
                    $bluh = preg_split('/{([A-z]+)}/',$item["Code"]);
                    $blam = [];
                    foreach($matches as $match)
                    {
                        $val = "";
                        if(array_key_exists($match,$option))
                        {
                            if($match == "Value" && is_array($option[$match]))$val = array_shift($option[$match]);
                            elseif($match == "Name")$val = $option[$match]."-".$id."-".rand();
                            else $val = $option[$match];
                        }
                        $blam[] = $val;
                    }
                    return array_shift($bluh) . implode(array_map(function($a,$b){return $a.$b;},$blam,$bluh));
                }
            }
            return null;
        }
        public function DrawByType($name,$value,$option=[])
        {
            $Type = Convert::GetType($value);
             foreach ($this->Components as $key => $component) 
             {
                 if(array_key_exists("Type",$component) && $component["Type"] == $Type)
                 {
                     return $this->Draw($key,$name,$value,$option);
                 }
             }
             return "";
        }
        public function Save($Url = "Components.include.php")
        {
            $str = "<?php\n";
            $str .= "\t\$Parts = [";
            $arr = [];
            foreach ($this->Components as $prop => $value)
            {
                $c = "\n\t\t\"".$prop."\" =>";
                $c.= "\n\t\t[";
                $c.= implode(",",array_map(function($k,$v){return "\n\t\t\t\"$k\"=>\"".addslashes($v)."\"";},array_keys($value),$value));
                $c.= "\n\t\t]";
                $arr[] = $c;
            }
            $str.= implode(",",$arr)."\n\t];\n";
            $str.="?>";
            $f = fopen($Url,"w+");
            fwrite($f,$str);
            fclose($f);
        }
        public static function Load($Url = "Components.include.php")
        {
            if(file_exists($Url))
            {
                include $Url;
                if(isset($Parts))
                {
                    
                    $c = new ComponentLibrary();
                    $c->Components = $Parts;
                    return $c;
                }
            }   
            return null;
        }
    }
    class MVCManager
    {
        public static function Initialise()
        {
            global $_RESOURCE_MODELS;
            global $_RESOURCE_CONTROLLERS;
            global $_RESOURCE_COMPONENTS;
            global $_RESOURCE_CONFIGURE;
            if($Configure = Configuration::Load())
            {
                $_RESOURCE_CONFIGURE = $Configure;
                if($Controllers = MVCManager::LoadClassesin($Configure->ControllerPath))
                {
                    array_unshift($Controllers,"DevController");
                    $_RESOURCE_CONTROLLERS = $Controllers;
                }
                if($Models = MVCManager::LoadClassesin($Configure->ModelPath))
                {
                    $_RESOURCE_MODELS = $Models;
                }
                if($Components = ComponentLibrary::Load($Configure->ComponentSheet))
                {
                    $_RESOURCE_COMPONENTS = $Components;
                }
                if($_RESOURCE_CONTROLLERS != null)
                {
                    $router = new Router();
                    $router->Render();
                }
            }else
            {
                MVCManager::FirstRun();
            }
        }
        public static function LoadClassesin($location)
        {
            if(file_exists($location))
            {
                $cap = get_declared_classes();
                foreach(scandir($location) as $value)
                    if (strpos($value, '.php') !== false)
                        include_once $location."/" . $value;
                return array_diff(get_declared_classes(),$cap);
            }
            return null;
        }
        public static function FirstRun()
        {
            $FileWrite = function($file,$str=null)
            {
                if(!file_exists($file))if(is_null($str))mkdir($file);else
                    {
                        $f = fopen($file,"w+");
                        fwrite($f,$str);
                        fclose($f);
                    }
            };
            $FileWrite(".htaccess","Options -MultiViews\nRewriteEngine On\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteRule ^ index.php [QSA,L]");
            $FileWrite("Controllers");
            $FileWrite("Controllers/Default.php","<?php\n\tclass Main{\n\t\tpublic function Main(\$Master = \"Master/Template\")\n\t\t{\n\t\t}\n\t\tpublic function index()\n\t\t{\n\t\t\treturn new Location(\"DevController\");\n\t\t}\n\t\tpublic function Error(\$Partial = \"Main/error\")\n\t\t{\n\t\t\treturn new View(\"<h1>404</h1><br><br><h2>Page not found</h2>\");\n\t\t}\n\t}\n?>");
            $FileWrite("Scripts");
            $FileWrite("Models");
            $FileWrite("Templates");
            $FileWrite("Templates/Main");
            $FileWrite("Templates/Main/error.php", "<?php \$this->PrintV(); ?>");
            $FileWrite("Templates/Master");
            $FileWrite("Templates/Master/Template.php", "<?php \$this->RenderContents(); ?>");
            $config = new Configuration();
            $config->Save();
            $c = new ComponentLibrary();
            $c->Save($config->ComponentSheet);
            MVCManager::Initialise();
        }
    }
    class DevController // this is where some magic happens// it's aplace to edit the configuration and update the models//
    {
        public function index()
        {
            global $_RESOURCE_CONFIGURE;
            global $_RESOURCE_COMPONENTS;
            $log =[];
            if(isset($_POST['sculpt']))
            {
                $m = new ModelController();
                $log = $m->Sculpt();
            }
            $config = $_RESOURCE_CONFIGURE;
            if(isset($_POST['updateConfig']))
            {
                $config = ModelController::getModelFromVars("Configuration",$_RESOURCE_COMPONENTS->StitchFromValues($_POST));
                $config->Save();
            }
            $t = ViewController::objectToForm($config);
            $keys = ["Database Location","Database Name","Database Username","Data Base Password","Models Directory","Templates Directory","Component Sheet","Controllers Directory","Scripts Directory","Default Controller","Error Page (Uses Default Controller)", "Alias for Developer Node"];
            $start = "\t\t".array_shift($t)."\n";
            $last = "\t\t".array_pop($t)."\n";
            $t = array_combine($keys,$t);
            $data = ViewController::doForEach($t,"\t\t\t\t<tr><td>{#}</td><td>{.}</td></tr>\n");
            $html = "<html>\n";
            $html .= "<head>\n";
            $html .= "</head>\n";
            $html .="\t<body>\n";
            $html .= $start;
            $html .= "<div class=\"container\">";
            $html .= "<div class=\"row\">";
            $html .= "<div class=\"col\">";
            $html .= "<H1>Edit Configuration</H1>";
            $html .="\t\t\t<table class=\"table\">\n";
            $html .= implode("",$data);
            $html .="\t\t\t\t<tr><td></td><td><button class= \"btn btn-primary\" style=\"width:100%;height:50px;\" name=\"updateConfig\">Update</button></td></tr>\n\n";
            $html .="\t\t\t</table>\n";
            $html .= $last;
            $html .= "</div>";
            $html .= "<div class=\"col\">";
            $html .= "<H1>Sculpt the Database</H1>";
            $html .= "<p>Sculpting the Database means that when you create new models or change the models in any way, like adding or removing a new property, you need to update the database to match your models</p>";
            $html .="\t\t\t<form method=\"POST\"><button name=\"sculpt\" class= \"btn btn-primary\" style=\"width:100%;padding:15px;\" href=\"".Router::BasePath().$_RESOURCE_CONFIGURE->devNode."/Sculpter\">Sculpt</button></form>\n";
            $html .="\t\t\t<br><br><h3>Change Output</h3>\n";
            $html .="\t\t\t<textarea class=\"form-control\" style=\"height:400px;\">";
            if(count($log)>0)
            {
                foreach ($log as $item) {
                    $html .= $item."\n";
                }
            }else 
            {
                $html .= "this will display any changes made to the database\n";
            }
           $html .= "</textarea>\n";
            $html .= "</div>";
            $html .= "</div>";
            $html .="<link rel=\"stylesheet\" href=\"https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css\" integrity=\"sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ\" crossorigin=\"anonymous\">\n";
            $html .="<script src=\"https://code.jquery.com/jquery-3.1.1.slim.min.js\" integrity=\"sha384-A7FZj7v+d/sdmMqp/nOQwliLvUsJfDHW+k9Omg/a/EheAdgtzNs3hpfag6Ed950n\" crossorigin=\"anonymous\"></script>\n";
            $html .="<script src=\"https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js\" integrity=\"sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb\" crossorigin=\"anonymous\"></script>\n";
            $html .="<script src=\"https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js\" integrity=\"sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn\" crossorigin=\"anonymous\"></script>\n";
            $html .="\t</body>\n";
            $html .= "</html>\n";
            return new Simple($html);
        }
    }
    class Router
    {
        private $Controllers;
        private $Config;
        public function Router()
        {
            global $_RESOURCE_CONFIGURE;
            $this->Config = $_RESOURCE_CONFIGURE;
            global $_RESOURCE_CONTROLLERS;
            $this->Controllers = $_RESOURCE_CONTROLLERS;
        }
        public function getArgumentList($method)
        {
            $arr = [];
            foreach ($method->getParameters() as $param) {
                $arr[$param->name] = $param->isDefaultvalueAvailable() ? $param->getDefaultValue() : $param->getClass();
            }
            return count($arr)>0 ? $arr:null;
        }
        public function getArgument($method,$name)
        {
            if($args = $this->getArgumentList($method))
            {
                if(array_key_exists($name,$args))
                {
                    return $args[$name];
                }
            }
            return null;
        }
        public function getDefaultMethod($class)
        {
            $r = new ReflectionClass($class);
            $methods = array_diff($r->getMethods(),array($r->getConstructor()));
            foreach ($methods as $method) {
                if($Default = $this->getArgument($method,"Default"))
                {
                    if($Default)return $method;
                }
            }
            foreach ($methods as $method) {
                return $method;
            }
            return null;
        }
        public function getUrl($Controller,$Method = null)
        {
            $r = new ReflectionClass($Controller);
            $m = ($Method)?$r->getMethod($Method) : "";
            $c = $r->getConstructor();
            $url = "";
            if($c && $Alias = $this->getArgument($c,"Alias"))$url = $Alias."/";else $url = $Controller."/";
            if($Controller == "DevController")$url = $this->Config->devNode."/";
            if($Controller == $this->Config->DefaultControl)$url = "";
            if($m && $Alias = $this->getArgument($m,"Alias"))$url .= $Alias;else $url .= $Method;
            return $url;
        }
        public function getRouteExpressions($Controller)
        {
            $routes = [];
            $default = $this->getDefaultMethod($Controller);// this is root
            $r = new ReflectionClass($Controller);
            $root = $Controller;
            if($con = $r->getConstructor())if($d = $this->getArgument($con,"Alias"))$root = "$d";
            if($Controller == "DevController")$root = $this->Config->devNode;
            $rt = $this->Config->DefaultControl == $Controller ? "":$root."(?![A-z0-9-_\/]+)";
            $routes[$default->name] = $this->Config->DefaultControl == $Controller ? $this->Config->DefaultControl:$rt;
            $methods = array_diff($r->getMethods(),array($default,$r->getConstructor()));
            foreach ($methods as $method) {
                $name = $method->name;
                $Data = [];
                $Optional = false;
                if($args = $this->getArgumentList($method))
                {
                    if(array_key_exists("Alias",$args))$name = $args["Alias"];
                    foreach ($args as $value) {
                        if(is_object($value) && is_a($value,"ReflectionClass"))
                        {
                            if($value->name == "RouteData")
                                $Data[] = "([A-z0-9-_]+)";
                            if($value->name == "NullData")
                                $Optional = true;
                        }
                    }
                }
                $expression = (empty($rt)?"":"$root\/").$name;
                if(count($Data)>0)$expression .= "\/".implode("\/",$Data);
                if($Optional)$expression.= "([A-z0-9-_\/]*)";else $expression.="(?![A-z0-9-_\/]+)";
                $routes[$method->name] = $expression;
            }
            return array_reverse($routes);
        }
        public function RenderView($c,$m,$view)
        {
            if(is_a($view,"View"))
            {
                $HeadScripts = [];
                $BodyScripts = [];
                $Master = "";
                $Partial = "";
                $r = new ReflectionClass($c);
                $testArray = function ($array1,$array2){return !is_null($array1) && is_array($array1) ? array_unique(array_merge($array2,$array1)): $array2;};
                if($Cont = $r->getConstructor())
                {
                    if($Controller = $this->getArgumentList($Cont))
                    {
                        if(array_key_exists("Master",$Controller))$Master = $Controller["Master"];
                        if(array_key_exists("HeadScripts",$Controller))$HeadScripts = $testArray($Controller["HeadScripts"],$HeadScripts);
                        if(array_key_exists("BodyScripts",$Controller))$BodyScripts = $testArray($Controller["BodyScripts"],$BodyScripts);
                    }
                }
                if($Meth = $r->getMethod($m))
                {
                    if($Method = $this->getArgumentList($Meth))
                    {
                        if(array_key_exists("Partial",$Method))$Partial = $Method["Partial"];
                        if(array_key_exists("HeadScripts",$Method))$HeadScripts = $testArray($Method["HeadScripts"],$HeadScripts);
                        if(array_key_exists("BodyScripts",$Method))$BodyScripts = $testArray($Method["BodyScripts"],$BodyScripts);
                    }
                }
                $controller = new ViewController($Master, $Partial,$HeadScripts,$BodyScripts);
                $controller->RenderView($view);
            }
            if(is_a($view,"JsonView"))
            {
                print json_encode($view->object);
            }
            if(is_a($view,"Location"))
            {
                $uri = $this->BasePath();
                if($view->Controller != "Default")
                {
                    if($class = $this->getUrl($view->Controller,$view->Route))
                    {
                        $uri .= $class.(count($view->Option) > 0 ? "/".implode('/',$view->Option) : "");
                    }
                }
                if($view->Controller != "Error")header("Location:".$uri);else $this->ErrorPage();
            }
            if(is_a($view,"Simple"))
            {
                $view->Draw();
            }
        }
        public static function BasePath()
        {
            $basepath = implode('/', array_slice(explode('/', $_SERVER['SCRIPT_NAME']), 0, -1)) . '/';
                return substr($_SERVER['REQUEST_URI'],0, strlen($basepath));
        }
        public function ErrorPage()
        { 
                global $_RESOURCE_CONFIGURE;
                header("Location:/".$_RESOURCE_CONFIGURE->errorPage."/",true);// send us to the error page//
        }
        public function Render()
        {
            $uri = trim($_SERVER['REQUEST_URI'],"/");
            if(empty($uri))$uri = $this->Config->DefaultControl;
            $tester = true;
            $controllers = $this->Controllers;
            while($tester)
            {
                $controller = array_shift($controllers);
                foreach($this->getRouteExpressions($controller) as $Action=>$Expression)
                {
                    if(preg_match("/".$Expression."/",$uri,$matches) > 0)
                    {
                        array_shift($matches);
                        if($list = $this->getArgumentList(new ReflectionMethod($controller,$Action)))
                        {
                            $RouteData = [];
                            $Optional = [];
                            $lst = array_filter($list,function($v){return is_object($v) && is_a($v,"ReflectionClass");});
                            $NonNull = array_filter($lst,function($v){return $v->name == "RouteData";});
                            if(count($NonNull)>0)$RouteData = array_splice($matches,0,count($NonNull));
                            if($last = array_shift($matches))$Optional = explode("/",trim($last,"/"));
                            $args = [];
                            foreach ($list as $value) {
                                if(is_object($value) && is_a($value,"ReflectionClass"))
                                {
                                    if($value->name == "RouteData"){if(count($NonNull)>0)array_push($args,new RouteData(array_shift($RouteData)));}
                                    if($value->name == "NullData"){if(count($Optional)>0)array_push($args,new NullData(array_shift($Optional)));else array_push($args,new NullData());}
                                }else
                                    array_push($args,$value);
                            }
                            $this->RenderView($controller,$Action,call_user_func_array(array(new $controller(),$Action),$args));
                        }else
                        {
                            $this->RenderView($controller,$Action,call_user_func(array(new $controller(),$Action)));
                        }
                        $tester = false;
                        break;
                    }
                }
                if(count($controllers)==0)
                    $tester = false;
            }
            if(count($controllers)==0)
            {
                //error page// bitch//
            }
        }
    }
    class ViewController
    {
        private $headScripts = [];
        private $bodyScripts = [];
        private $MasterVars;
        private $PartialVars;
        private $MasterTemplate;
        private $PartialTemplate;
        private $LoadingMaster = true;
        private $Components;
        public function ViewController($MasterTemplate, $PartialTemplate,$headScripts = [],$bodyScripts=[])
        {
            global $_RESOURCE_COMPONENTS;
            $this->Components = $_RESOURCE_COMPONENTS;
            $this->MasterTemplate = $MasterTemplate;
            $this->PartialTemplate = $PartialTemplate;
            $this->headScripts = $headScripts;
            $this->bodyScripts = $bodyScripts;
        }
        public function LoadScripts($body=false,$defaultScripts = [])// loads our scripts into their places//
        {
            global $_RESOURCE_CONFIGURE;
            $test = $body ? array_unique(array_merge($this->bodyScripts,$defaultScripts)):
                array_unique(array_merge($this->headScripts,$defaultScripts));
            $path = $_RESOURCE_CONFIGURE->ScriptsPath;
            $filter =function($i){return file_exists($i);};
            $js = array_filter(array_map(function($p,$f){return $p."/".$f.".js";},array_fill(0,count($test),$path),$test),$filter);
            $css = array_filter(array_map(function($p,$f){return $p."/".$f.".css";},array_fill(0,count($test),$path),$test),$filter);
            foreach ($js as $value)print "<script src=\"/$value\"></script>\n";
            foreach ($css as $value)print "<link href=\"/$value\" rel=\"stylesheet\">\n";
        }
        public function PrintV($var=null,$override=null,$line=false)
        {
            if($p = $this->V($var,$override))print $p.($line?"<br>":"");
        }
        public static function getVal($var,$val)
        {
            if(is_null($var) || is_null($val))return null;
            $var = (is_array($var))?$var:array($var);
            $held = $val;
            foreach($var as $v)
            {
                    if(is_array($held) && array_key_exists($v,$held))$held = $held[$v];
                    elseif(is_object($held) && isset($held->$v))$held = $held->$v;
                    else return null;
            }
            return $held;
        }
        public function V($var=null,$override=null)
        {
            $testVar = (is_null($override))?($this->LoadingMaster)?$this->MasterVars:$this->PartialVars:$override;
            if(is_null($var))return (!is_null($testVar))?$testVar:null;
            if(!is_null($var))
            {
                return ViewController::getVal($var,$testVar);
            }  
        }
        public static function objectToForm($object,$action="",$method="POST",$Replace=[])
        {
            global $_RESOURCE_COMPONENTS;
            $tmp=["Start"=>"<form action=\"$action\" method=\"$method\">\n"];
            foreach (get_object_vars($object) as $Name => $Value) {
                $n = (array_key_exists($Name,$Replace))?$Replace[$Name]:$Name;
                $tmp[$Name] = $_RESOURCE_COMPONENTS->DrawByType($n,$Value);
            }
            $tmp["End"] = "</form>\n";
            return $tmp;
        }
        public static function doForEach($var,$pattern)
        {
            if(!is_array($var))$var = [$var];
            preg_match_all('/{([A-z0-9.#]+)}/', $pattern, $matches, PREG_SET_ORDER, 0);
            $matches = ($matches)?array_map(function($f){return $f[1];},$matches):null;
            $a = [];
                foreach ($var as $key =>$item) 
                {
                    $s = preg_split('/{([A-z0-9.#]+)}/',$pattern);
                    if(is_object($item))$item = get_object_vars($item);
                    $bl = [];
                        foreach ($matches as $match)
                        {
                            if(is_array($item))
                            {
                                if(array_key_exists($match,$item))
                                {
                                    array_push($bl,$item[$match]);
                                }
                            }
                            else
                            {
                                if($match == ".")
                                {
                                    array_push($bl,$item);
                                }elseif($match=="#")
                                    array_push($bl,$key);
                                else array_push($bl,"");
                            }
                            
                        }
                    array_push($a,array_shift($s) . implode(array_map(function($a,$b){return $a.$b;},$bl,$s)));
                }
            return $a;
        }
        public function RenderContents()
        {
            global $_RESOURCE_CONFIGURE;
            $this->LoadingMaster = false;
            $ThePartial = $_RESOURCE_CONFIGURE->TemplatePath."/".$this->PartialTemplate.".php";
            if(file_exists($ThePartial))
            {
                include $ThePartial;
            }
        }
        public function RenderView($view)
        {
            global $_RESOURCE_CONFIGURE;
            $this->LoadingMaster = true;
            $TheMaster = $_RESOURCE_CONFIGURE->TemplatePath."/".$this->MasterTemplate.".php";
            $this->MasterVars = $view->Master;
            $this->PartialVars = $view->Partial;
            if(file_exists($TheMaster))
            {
                include $TheMaster;
            }
        }
    }
    class ModelController
    {
        public $ErrorLog = [];
        public function getProperties($Model)
        {   
            $Object = is_object($Model)?$Model:new $Model();
            $Model = is_object($Model)?get_class($Model):$Model;
            $Ancestors = $this->getAncestors($Model);
            $Properties = [];
            $r = new ReflectionClass($Model);
            
            foreach($r->getProperties(ReflectionProperty::IS_PUBLIC) as $Prop)
            {
                $value = $Prop->getValue($Object);
                if(!is_null($value) && $Prop->getDeclaringClass()->name == $Model)
                {
                    if(is_array($value) && isset($value[0]) && is_object($value[0]))
                    {
                        $sClass = $this->getAncestors(get_class($value[0]))[0];
                        $Properties[$sClass."_".$Ancestors[0]] = ["Type"=>"Constraint","Value"=>[$sClass,$Ancestors[0]]];
                    }
                    else if(is_object($value) && is_a($value,"Relationship"))
                    {
                        $sClass = $this->getAncestors($value->To)[0];
                        $t_obj = new $sClass->To();
                        foreach(((new ReflectionClass($value->To))->getProperties(ReflectionProperty::IS_PUBLIC)) as $fProp)
                        {
                            $fVal = $fProp->getValue($t_obj);
                            if(!(is_a($fVal,"ManyRelation") && $fVal->To == $Model && is_a($value,"OneRelation")))
                            {
                                $Properties[$sClass."_".$Ancestors[0]] = ["Type"=>"Constraint","Value"=>[$sClass,$Ancestors[0]]];
                            }
                        }
                    }
                    else
                    {
                        $Properties[$this->getName($Model).$Prop->name] = Convert::ClasstoTable($value);
                    }
                }
            }
            return $Properties;
        }
        public function getName($Model,$Act = false)
        {
            $Ancestors = $this->getAncestors($Model);
            if(!$Act)array_shift($Ancestors);
            return (count($Ancestors)>0)?implode('_',array_slice($Ancestors,array_search($Model,$Ancestors)))."_":"";
        }
        public function getTableProperties($Model)
        {
            if($create = $this->DoQuery("SHOW CREATE TABLE ".$Model))
            {
                preg_match_all("/`([A-z]+)` ([A-z1234567890()]+) DEFAULT ('[A-z1234567890().:\- ]+'|''|NULL)/", $create[0]["Create Table"], $o);
                preg_match_all("/CONSTRAINT `(\S+)` FOREIGN KEY \(`(\S+)`\) REFERENCES `(\S+)` \(`(\S+)`\)/", $create[0]["Create Table"], $c);
                $cons = array_map(function($a,$b,$c){return ["Name"=>$a,"Type"=>"Constraint","Value"=>[$b,$c]];},$c[1],$c[2],$c[3]);
                $vals = array_map(function($a,$b,$c){return ["Name"=>$a,"Type"=>$b,"Value"=>($c == "NULL")?"":trim($c,"'")];},$o[1],$o[2],$o[3]);
                $Properties = [];
                foreach(array_merge($vals,$cons) as $value)
                {
                    if($value["Name"] != "id" && $value["Name"] != "ClassType")
                    $Properties[$value["Name"]] = ["Type"=>$value["Type"],"Value"=>$value["Value"]];
                }
                return $Properties;
            }
            return null;
        }
        public function createTable($Model,$Values)
        {
            $constants = array_filter($Values,function($i){return ($i["Type"] == "Constraint");});
            $fields = array_filter($Values,function($i){return ($i["Type"] != "Constraint");});
            $query = "CREATE TABLE ". $Model . " (\n`id` INT(10) AUTO_INCREMENT NOT NULL PRIMARY KEY,\n"
                .implode(",\n", array_map(function($k,$v){return "`".$k."` ". $v["Type"] .(is_null($v["Value"])?"":" DEFAULT '".addslashes($v["Value"])."'");},array_keys($fields),$fields)).
                ((count($constants)>0)?",\n".implode(",\n", array_map(function($k,$v){return "CONSTRAINT `$k` FOREIGN KEY (`".$k."_FK`) REFERENCES `".$v["Value"][1]."` (`id`)";},array_keys($constants),$constants)):"");
            $query .= ",\n`ClassType` VARCHAR(255) DEFAULT '$Model')\n";
            return $query;
        }
        public static function getAncestors($Model)
        {
            $Ancestors = [$Model];
            $class = new ReflectionClass($Model);
            while ($class = $class->getParentClass()) {
                array_unshift($Ancestors,$class->name);
            }
            return $Ancestors;
        }
        public function getLastID()
        {
            return $this->DoQuery();
        }
        public function DoQuery($query = null)
        {
            global $_RESOURCE_CONFIGURE;
            $return_arr = null;
			$dbcon = new mysqli($_RESOURCE_CONFIGURE->DBaddress,$_RESOURCE_CONFIGURE->DBuser,$_RESOURCE_CONFIGURE->DBpass,$_RESOURCE_CONFIGURE->DBname);
			if (isset($dbcon->connect_err))
               array_push($this->ErrorLog,"Connection Error:".$dbcon->connect_err."<br>");
            else 
            {
                if($result = $dbcon->query($query))
                {
                    if (is_a($result,"mysqli_result")) {
				        $return_arr = $result->fetch_all(MYSQLI_ASSOC);
				        $result->free();
                    }else
                    {
                        return ($dbcon->insert_id != 0)?$dbcon->insert_id:null;
                    }
                }else
                {
                    array_push($this->ErrorLog,"Mysql Error: ".$dbcon->error. "<br>");
                }
            }
			$dbcon->close();
			return $return_arr;
        }
        public function LongQuery($query)
        {
            global $_RESOURCE_CONFIGURE;
            $return_arr = null;
			$dbcon = new mysqli($_RESOURCE_CONFIGURE->DBaddress,$_RESOURCE_CONFIGURE->DBuser,$_RESOURCE_CONFIGURE->DBpass,$_RESOURCE_CONFIGURE->DBname);
			if (isset($dbcon->connect_err))
               array_push($this->ErrorLog,"Connection Error:".$dbcon->connect_err."<br>");
            else 
            {
                if(!$dbcon->multi_query($query))
                {
                    array_push($this->ErrorLog,"Mysql Error: ".$dbcon->error. "<br>");
                }
            }
			$dbcon->close();
			return $return_arr;
        }
        public function Sculpt()
        {
            $qStack = [];
            $Classes = [];
            $ChangeTracker=[];
            global $_RESOURCE_MODELS;
            if(count($_RESOURCE_MODELS)==0) return;
            foreach ($_RESOURCE_MODELS as $Model) {
                $obj = new ReflectionClass($Model);
                $value = $obj->newInstance();
                $r = $this->getProperties($Model);
                $dr = $this->getAncestors($Model)[0];
                $a = array_filter($r,function($i){return ($i["Type"] != "Constraint");});
                if(!array_key_exists($dr,$Classes))$Classes[$dr] = $a; else $Classes[$dr] = array_merge($Classes[$dr],$a);
                foreach(array_filter($r,function($i){return ($i["Type"] == "Constraint");}) as $k=>$v)
                {
                    $d = $this->getAncestors($v["Value"][0])[0];
                    $f = [$k."_FK"=>["Type"=>"int(100)","Value"=>null]];
                    $f = ($d != $this->getAncestors($v["Value"][1])[0])?array_merge([$k=>$v],$f):$f;// for self refrence//
                    if(!array_key_exists($d,$Classes))$Classes[$d] = $f; else $Classes[$d] = array_merge($Classes[$d],$f);
                }
            }
            $t = $this->DoQuery("show tables");
            if(!$t)$t = [];
                $c = array_combine(array_keys($Classes),array_map(function($v){return strtolower($v);},array_keys($Classes)));
                $t = array_map(function($v){return implode("",$v);},$t);
                $Up = array_diff($c,$t);
                $Down = array_diff($t,$c);
                foreach ($Up as $key => $value) {
                    array_push($qStack,$this->createTable($key,$Classes[$key]));
                    array_push($ChangeTracker, "Created Table: $key");
                }
                foreach ($Down as $Table) {
                    array_push($qStack,"DROP TABLE $Table");
                    array_push($ChangeTracker,"Dropped Table: $Table");
                }

            $sort = function($r,$down=true)
            {
                $c = array_filter($r,function($i){return ($i["Type"] == "Constraint");});
                $p = array_filter($r,function($i){return ($i["Type"] != "Constraint");});
                return ($down)?array_merge($c,$p):array_merge($p,$c);
            };
            $map = function($k,$v)
            {
                return "\n".(($v["Type"] == "Constraint")?
                    "ADD CONSTRAINT `$k` FOREIGN KEY (`".$k."_FK`) REFERENCES `".$v["Value"][1]."` (`id`) ON UPDATE CASCADE ON DELETE CASCADE":
                    "ADD COLUMN " .$k." ".$v["Type"] .(!is_null($v["Value"])? " DEFAULT '" . $v["Value"] . "'":""));
            };
            foreach ($Classes as $Model => $Properties) {
                if($Table = $this->getTableProperties($Model))
                {
                    $Up = $sort(array_diff_key($Properties,$Table),false);
                    $Down = $sort(array_diff_key($Table,$Properties));
                    foreach ($Up as $key => $value) {array_push($ChangeTracker,"Added: \"".$key."\" to Table: ".strtolower($Model));}
                    foreach ($Down as $key => $value) {array_push($ChangeTracker,"Removed: \"".$key."\" From Table: ".strtolower($Model));}
                    if((count($Up)>0 || count($Down)>0))
                    {
                        $Query = "ALTER TABLE $Model " . implode(',',array_map(function($k,$v){return "\n".(($v["Type"] == "Constraint")?"DROP FOREIGN KEY `".$k."`":"DROP COLUMN " .$k);},array_keys($Down),$Down)).
                            ((count($Up)>0 && count($Down)>0)?",":"").implode(',',array_map($map,array_keys($Up),$Up));
                        array_push($qStack,$Query);
                    }
                } 
            }
            if(count($qStack)>0)
            {
                $this->LongQuery("SET FOREIGN_KEY_CHECKS=0;".implode(";",$qStack));
            }
            return $ChangeTracker;
        }
        public function Get($Model,$Where=[])
        {
            $a = $this->getAncestors($Model)[0];
            array_push($Where,"ClassType = '".$Model."'");
            $query = "SELECT * FROM $a";
            if(count($Where)>0)$query .= " WHERE " . implode(" AND ",$Where);
            $result = [];
            foreach ($this->DoQuery($query) as $item) {
                $Object = new $Model();
                $Object->id = $item["id"];
                $t = array_combine(array_map(function($i){return array_reverse(explode("_",$i))[0];},array_keys($item)),$item);
                foreach((new ReflectionClass($Model))->getProperties(ReflectionProperty::IS_PUBLIC) as $Prop)
                {
                    $val = $Prop->getValue($Object);
                    $type = Convert::GetType($val);
                    if($type == "array" && isset($val[0]))
                        if($inType = Convert::GetType($val[0]))
                        { 
                            $Prop->SetValue($Object,$this->Get($inType,[$this->getAncestors($inType)[0]."_".$a."_FK =".$t["id"]]));
                        }
                    if(array_key_exists($Prop->name,$t))
                        $Prop->SetValue($Object,Convert::StringtoType($type,$t[$Prop->name]));
                }
                $Object->c = $this->Unique($Object);
                array_push($result,$Object);
            }
            return $result;
        }
        public function Unique($item)
        {
            $str = "";
            $vars =get_object_vars($item);
            ksort($vars);
            return implode("",array_map(function($a,$b){return ($a != "id" && $a != "c")?$a . ((gettype($b)!="array")?Convert::toString($b):"") : "";},array_keys($vars),$vars));
        }
        private function Runit($ie,$class=null,$id=null)
            {
                $stack = [];
                foreach ($ie as $item) {
                    $cs = get_class($item);
                    $c = $this->getAncestors($cs,true);
                    $arrays = [];
                    $filtered = [];
                    foreach ($c as $ancestor) {
                        $tmp = new $ancestor();
                        foreach ((new Reflectionclass($ancestor))->getProperties(ReflectionProperty::IS_PUBLIC) as $Prop) {
                            $value = $Prop->getValue($item);
                            $def = $Prop->getValue($tmp);
                            $type = Convert::GetType($def);      
                            if($Prop->getDeclaringClass()->name == $ancestor && !is_null($value))
                            { 
                                if(is_array($value)){$arrays[$Prop->name] = $value;}else{ $filtered[$this->getName($ancestor).$Prop->name] = Convert::toString($value);}
                            }
                        }
                    }
                    if(isset($item->c))
                    { 
                        if($item->c != $this->Unique($item))
                        {
                            if($class && $id)
                                $filtered[$c[0]."_".$this->getAncestors($class)[0]."_FK"]=$id;
                            $query = "UPDATE ". $c[0] . " SET ".implode(", ",array_map(function($k,$v){return $k." = '".$v."'";},array_keys($filtered),$filtered))." WHERE id='".$item->id."'";
                            array_push($stack,$query);
                        }
                        foreach ($arrays as $ar) {
                            foreach($this->Runit($ar,$c[0],$item->id) as $s){array_push($stack,$s);}
                        }
                    }
                    else
                    {
                        if($class && $id){$filtered[$c[0]."_".$this->getAncestors($class)[0]."_FK"] = $id;}
                        $filtered["ClassType"] = $cs;
                        $query = "INSERT INTO ". $c[0] . " (".implode(", ",array_map(function($f){return $f;},array_keys($filtered))).") VALUES (".implode(", ",array_map(function($f){return "'".$f."'";},$filtered)).")";
                        array_push($stack,$query);
                    }
                }
                return $stack;
            }
        public function Save($items)
        {
            $Inserts = [];
            $items = is_array($items)?$items:array($items);
            $runned = $this->Runit($items);
            foreach ($runned as $s) {
                array_push($Inserts,$this->DoQuery($s));
            }
            return $Inserts;
        }
        public function Delete($items)
        {
            $items = is_array($items)?$items:array($items);
            foreach ($items as $item)
                if(isset($item->id))
                {
                    $c = get_class($item);
                    $a = $this->getAncestors($c);
                    $query = "DELETE FROM " . $a[0] . " WHERE id=".$item->id;
                    $this->DoQuery($query);
                }
        }
        public static function getModelFromVars($model,$var,$nKey=[])// designed for $_GET and $_POST//
        {
            $nKey = array_combine(array_values($nKey),array_keys($nKey));
            $object = (is_object($model))?$model:new $model();
            $class = (is_object($model))?get_class($model):$model;
            $r = new ReflectionClass($class);
            foreach ($var as $name => $value) {
                    if(array_key_exists($name,$nKey))$name = $nKey[$name];
                    if($r->hasProperty($name))
                    {
                        $prop = $r->getProperty($name);
                        $prop->setValue($object,$value);
                    }
                }
            return $object;
        }
    }
    

?>