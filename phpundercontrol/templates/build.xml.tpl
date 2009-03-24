{use $componentName, $componentDeps = array(), $needsDatabase, $phps, $dsns, $phpCcVersion}
<?xml version="1.0" encoding="UTF-8"?>
<project name="ezc{$componentName}" default="build" basedir=".">
  <target name="clean">
    <delete>
      <fileset dir="$\{basedir\}/build/logs" includes="**.*" />
    </delete>
    <delete>
      <fileset dir="$\{basedir\}/build/api" includes="**.*" />
    </delete>
    <delete>
      <fileset dir="$\{basedir\}/build/coverage" includes="**.*" />
    </delete>
  </target>  
 <target name="checkout">

  <!-- update ezc scripts -->
  <exec dir="$\{basedir\}/source" executable="svn">
   <arg line="up scripts"/>
  </exec>

  <!-- update base component -->
  <exec dir="$\{basedir\}/source" executable="svn">
   <arg line="up trunk/Base"/>
  </exec>

  <!-- console tools, used by unit test -->
  <exec dir="$\{basedir\}/source" executable="svn">
   <arg line="up trunk/ConsoleTools"/>
  </exec>

  <!-- update unit test component -->
  <exec dir="$\{basedir\}/source" executable="svn">
   <arg line="up trunk/UnitTest"/>
  </exec>

  <!-- component itself -->
  <exec dir="$\{basedir\}/source" executable="svn">
   <arg line="up trunk/{$componentName}"/>
  </exec>

  <!-- update dependent components -->
  {foreach $componentDeps as $depComponentName}
  <exec dir="$\{basedir\}/source" executable="svn">
   <arg line="up trunk/{$depComponentName}"/>
  </exec>
  {/foreach}

  <!-- re-setup ezc environment, for new components -->
  <exec dir="$\{basedir\}/source" executable="./scripts/setup-env.sh"/>

 </target>
 
 <!-- build docs -->
 <target name="phpdoc">
  <exec dir="$\{basedir\}/source/trunk/{$componentName}/src" executable="phpdoc" logerror="on">
   <arg line="--title '$\{ant.project.name\}' -ct type -ue on -i 'autoload*' -t $\{basedir\}/build/api -tb /home/dotxp/dev/phpUnderControl/data/phpdoc -o HTML:Phpuc:phpuc -d ."/>
  </exec>
 </target>

 <!-- check coding style -->
 <target name="phpcs">
  <exec dir="$\{basedir\}/source/trunk/" executable="phpcs" output="$\{basedir\}/build/logs/checkstyle.xml" error="$\{basedir\}/build/logs/checkstyle.error.xml">
   <arg line="--report=checkstyle --standard=EZC --ignore=tests {$componentName}/src" />
  </exec>
 </target>

 <!-- build unittests -->
 {var
    $logFile = '',
    $target  = '',
    $logs    = array(),
    $targets = array()
 }

 {foreach $phps as $php}

   {if $needsDatabase}
     
     {foreach $dsns as $dsnName => $dsn}
       
  	   {$target  = 'php-' . $php . '-' . $dsnName}
       {$logFile = '${basedir}/build/tmp/' . $target . '.xml'}
  
       <target name="{$target}">
        <exec dir="$\{basedir\}/source/trunk/" executable="php-{$php}" failonerror="false">
         <arg line="UnitTest/src/runtests.php
                    {if $php == $phpCcVersion}
                    -c             $\{basedir\}/build/coverage
                    --log-pmd      $\{basedir\}/build/logs/phpunit.pmd.xml
                    --log-metrics  $\{basedir\}/build/logs/phpunit.metrics.xml
                    --coverage-xml $\{basedir\}/build/logs/phpunit.coverage.xml
                    {/if}
                    -x            '{$logFile}'
  				    -D            '{$dsn}'
                    {$componentName}"/>
        </exec>
       </target>
       {$logs[] = $logFile}
  	   {$targets[] = $target}

     {/foreach}
  
   {else}
  
  	   {$target  = 'php-' . $php}
       {$logFile = '${basedir}/build/tmp/' . $target . '.xml'}
  
       <target name="{$target}">
        <exec dir="$\{basedir\}/source/trunk/" executable="php-{$php}" failonerror="false">
         <arg line="UnitTest/src/runtests.php
                    {if $php == $phpCcVersion}
                    -c             $\{basedir\}/build/coverage
                    --log-pmd      $\{basedir\}/build/logs/phpunit.pmd.xml
                    --log-metrics  $\{basedir\}/build/logs/phpunit.metrics.xml
                    --coverage-xml $\{basedir\}/build/logs/phpunit.coverage.xml
                    {/if}
                    -x            '{$logFile}'
                    {$componentName}"/>
        </exec>
       </target>

       {$logs[] = $logFile}
  	   {$targets[] = $target}
  
   {/if}

 {/foreach}
     
 {* Create merge for all DSNs *}
  
 <target name="merge">
    <exec executable="/home/dotxp/dev/phpUnderControl/bin/phpuc.php" dir="$\{basedir\}" failonerror="true">
      <arg line="merge-phpunit
                 -b {str_join( $targets, ',' )}
                 -i {str_join( $logs   , ',' )}
                 -o $\{basedir\}/build/logs/log.xml"/>
    </exec>
  </target>
  

 <!-- clean up unit test directory -->
 <target name="cleanup">
  <delete dir="$\{basedir\}/source/trunk/run-tests-tmp"/>
  <delete>
          <fileset dir="$\{basedir\}/build/tmp">
            <include name="*"/>
          </fileset>
    </delete>
 </target>

 <!-- originally: <target name="build" depends="checkout,phpunit,cleanup"/> -->
 <!-- put parts together -->
 <target name="build" depends="clean,checkout,phpdoc,phpcs,{str_join( $targets, ',' )},merge,cleanup"/>
</project>
