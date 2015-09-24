<?php 
require_once dirname(__FILE__) . "/Pheanstalk/PheanstalkInterface.php";
require_once dirname(__FILE__) . "/Pheanstalk/Connection.php";
require_once dirname(__FILE__) . "/Pheanstalk/Exception.php";
require_once dirname(__FILE__) . "/Pheanstalk/Exception/ClientException.php";
require_once dirname(__FILE__) . "/Pheanstalk/Exception/SocketException.php";
require_once dirname(__FILE__) . "/Pheanstalk/Exception/ServerException.php";
require_once dirname(__FILE__) . "/Pheanstalk/Exception/ConnectionException.php";
require_once dirname(__FILE__) . "/Pheanstalk/Socket.php";
require_once dirname(__FILE__) . "/Pheanstalk/Socket/NativeSocket.php";
require_once dirname(__FILE__) . "/Pheanstalk/Socket/StreamFunctions.php";
require_once dirname(__FILE__) . "/Pheanstalk/Socket/WriteHistory.php";
require_once dirname(__FILE__) . "/Pheanstalk/Response.php";
require_once dirname(__FILE__) . "/Pheanstalk/Response/ArrayResponse.php";
require_once dirname(__FILE__) . "/Pheanstalk/ResponseParser.php";
require_once dirname(__FILE__) . "/Pheanstalk/Command.php";
require_once dirname(__FILE__) . "/Pheanstalk/Command/AbstractCommand.php";
require_once dirname(__FILE__) . "/Pheanstalk/Command/UseCommand.php";
require_once dirname(__FILE__) . "/Pheanstalk/Command/PutCommand.php";
require_once dirname(__FILE__) . "/Pheanstalk/Command/DeleteCommand.php";
require_once dirname(__FILE__) . "/Pheanstalk/Command/WatchCommand.php";
require_once dirname(__FILE__) . "/Pheanstalk/Command/ReserveCommand.php";
require_once dirname(__FILE__) . "/Pheanstalk/Command/StatsJobCommand.php";
require_once dirname(__FILE__) . "/Pheanstalk/Pheanstalk.php";
require_once dirname(__FILE__) . "/Pheanstalk/YamlResponseParser.php";
require_once dirname(__FILE__) . "/Pheanstalk/Job.php";
use Pheanstalk\Pheanstalk;
use Pheanstalk\Job;

class Beanstalk
{


    public $pheanstalk;
    public $job;
    public function __construct()
    {
        $this->pheanstalk =  new Pheanstalk(C('beanstalkd.ip'));
        return $this->pheanstalk;
    }

    public function job($job_id, $data = null)
    {
    return new Job($job_id, $data);
    }
}
