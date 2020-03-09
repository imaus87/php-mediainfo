<?php

namespace Mhor\MediaInfo\Runner;

use Symfony\Component\Process\Process;

class MediaInfoCommandRunner
{
    const FORCED_OLDXML_OUTPUT_FORMAT_ARGUMENTS = ['--OUTPUT=OLDXML', '-f'];
    const XML_OUTPUT_FORMAT_ARGUMENTS = ['--OUTPUT=XML', '-f'];

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var Process
     */
    protected $process;

    /**
     * @var array
     */
    protected $command = ['mediainfo'];

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @param string  $filePath
     * @param array  $command
     * @param array   $arguments
     * @param Process $process
     * @param bool    $forceOldXmlOutput
     */
    public function __construct(
        $filePath,
        array $command = null,
        array $arguments = null,
        Process $process = null,
        $forceOldXmlOutput = false
    ) {
        $this->filePath = $filePath;
        if ($command !== null) {
            $this->command = $command;
        }

        $this->arguments = self::XML_OUTPUT_FORMAT_ARGUMENTS;
        if ($forceOldXmlOutput) {
            $this->arguments = self::FORCED_OLDXML_OUTPUT_FORMAT_ARGUMENTS;
        }

        if ($arguments !== null) {
            $this->arguments = $arguments;
        }

        // /path/to/mediainfo $MEDIAINFO_VAR0 $MEDIAINFO_VAR1...
        // args are given through ENV vars in order to have system escape them

        $args = $this->arguments;
        array_unshift($args, $this->filePath);

        $env = [
            'LANG' => setlocale(LC_CTYPE, 0),
        ];
        $finalCommand = $this->command;

        foreach ($args as $value) {
            $finalCommand[] = $value;
        }

        $this->process = new Process($finalCommand, null, $env);
    }

    /**
     * @throws \RuntimeException
     *
     * @return string
     */
    public function run()
    {
        $this->process->run();
        if (!$this->process->isSuccessful()) {
            throw new \RuntimeException($this->process->getErrorOutput());
        }

        return $this->process->getOutput();
    }

    /**
     * Asynchronously start mediainfo operation.
     * Make call to MediaInfoCommandRunner::wait() afterwards to receive output.
     */
    public function start()
    {
        // just takes advantage of symfony's underlying Process framework
        // process runs in background
        $this->process->start();
    }

    /**
     * Blocks until call is complete.
     *
     * @throws \Exception        If this function is called before start()
     * @throws \RuntimeException
     *
     * @return string
     */
    public function wait()
    {
        if ($this->process == null) {
            throw new \Exception('You must run `start` before running `wait`');
        }

        // blocks here until process completes
        $this->process->wait();

        if (!$this->process->isSuccessful()) {
            throw new \RuntimeException($this->process->getErrorOutput());
        }

        return $this->process->getOutput();
    }
}