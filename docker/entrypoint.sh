#!/bin/bash
set -e

# ---------------------------------------------------------------------------
# Entrypoint for the OpenWebSoccer-Sim Docker image.
#
# Starts the cron daemon (with the recommended job schedules) and then
# launches Apache in the foreground.
#
# Environment variables:
#   WEBSOCCER_CRON_ENABLED   Set to "0" to disable cron jobs.  Default: 1
#   WEBSOCCER_JOB_SECTOKEN   Security key that matches the webjobexecution_key
#                            setting in the AdminCenter.  Default: -
# ---------------------------------------------------------------------------

if [ "${WEBSOCCER_CRON_ENABLED:-1}" = "1" ]; then
    SECTOKEN="${WEBSOCCER_JOB_SECTOKEN:--}"

    # Write the crontab with the two recommended job schedules:
    #   1. every minute       â€“ simulate open matches
    #   2. every 15 minutes    â€“ all other jobs
    cat <<EOF | crontab -
* * * * * /usr/local/bin/php /var/www/html/webservices/executeJob.php sectoken=${SECTOKEN} jobid=sim >> /proc/1/fd/1 2>&1
*/15 * * * * /usr/local/bin/php /var/www/html/webservices/executeJob.php sectoken=${SECTOKEN} jobid=addplyr,extransf,usractv,stats,stadium >> /proc/1/fd/1 2>&1
EOF

    # Start the cron daemon in the background
    cron
    echo "Cron daemon started (security key: ${SECTOKEN})"
else
    echo "Cron daemon disabled (WEBSOCCER_CRON_ENABLED=0)"
fi

# Start Apache in the foreground
exec apache2-foreground
