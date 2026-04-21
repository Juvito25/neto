#!/bin/bash
# Keep localtunnel alive by pinging periodically

while true; do
    # Check if tunnel is running
    if curl -s -o /dev/null -w "%{http_code}" http://localhost/ 2>/dev/null | grep -q "200"; then
        echo "$(date): Server OK"
    else
        echo "$(date): Server down, restarting..."
    fi
    
    # If tunnel process is not running, restart it
    if ! pgrep -f "localtunnel" > /dev/null; then
        echo "$(date): Tunnel down, restarting..."
        pkill -f localtunnel 2>/dev/null
        sleep 1
        nohup npx localtunnel --port 80 > /tmp/tunnel_url.txt 2>&1 &
        sleep 5
        if [ -f /tmp/tunnel_url.txt ]; then
            echo "Tunnel: $(cat /tmp/tunnel_url.txt)"
        fi
    fi
    
    sleep 60
done