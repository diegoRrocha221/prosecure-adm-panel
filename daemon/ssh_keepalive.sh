# daemon/ssh_keepalive.sh - Script para gerenciar o daemon
#!/bin/bash

DAEMON_DIR="/var/www/html/prosecure-adm-panel/daemon"
PID_FILE="/var/run/prosecure-ssh-daemon.pid"
LOG_FILE="/var/log/prosecure-ssh-daemon.log"

case "$1" in
    start)
        if [ -f $PID_FILE ]; then
            echo "Daemon already running (PID: $(cat $PID_FILE))"
            exit 1
        fi
        
        echo "Starting SSH KeepAlive Daemon..."
        nohup /usr/bin/php $DAEMON_DIR/ssh_keepalive.php >> $LOG_FILE 2>&1 &
        echo $! > $PID_FILE
        echo "Daemon started (PID: $(cat $PID_FILE))"
        ;;
    
    stop)
        if [ ! -f $PID_FILE ]; then
            echo "Daemon not running"
            exit 1
        fi
        
        echo "Stopping SSH KeepAlive Daemon..."
        kill $(cat $PID_FILE)
        rm -f $PID_FILE
        echo "Daemon stopped"
        ;;
    
    restart)
        $0 stop
        sleep 2
        $0 start
        ;;
    
    status)
        if [ -f $PID_FILE ]; then
            PID=$(cat $PID_FILE)
            if ps -p $PID > /dev/null; then
                echo "Daemon is running (PID: $PID)"
            else
                echo "Daemon is not running (stale PID file)"
                rm -f $PID_FILE
            fi
        else
            echo "Daemon is not running"
        fi
        ;;
    
    *)
        echo "Usage: $0 {start|stop|restart|status}"
        exit 1
        ;;
esac