
* After install:
    apt-get update
    apt-get install apache2
    apt-get install php5 
    apt-get install libapache2-mod-php5
    apt-get install mysql-server
    apt-get install php5-mysql 
    apt-get install make
    apt-get install php-pear
    apt-get install libpcre3-dev
    apt-get install imagemagick
    apt-get install highlight 
    apt-get install php5-curl
    apt-get install php5-dev
    apt-get install php-pear php5-dev make libpcre3-dev 
    apt-get install php-apc 
    apt-get install git 
    apt-get install libssh2-1-dev libssh2-php 
* Configure php (add extension=apc.so)
    vim /etc/php5/apache2/php.ini  
    php -m | grep ssh2 
* Install pypedia software and data in /var/www:
    tar xvf /tmp/pypedia.tar 
    chown -R www-data:www-data /var/www/pypedia
    ...
    vi LocalSettings.php 
* Edit /etc/apache2/sites-available/apache.conf:

    <VirtualHost *:80>
        ServerAdmin admin@pypedia.org
        DocumentRoot /var/www/pypedia
        ServerName www.pypedia.org

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined
    </VirtualHost>

    <VirtualHost *:80>
        ServerName pypedia.org
        Redirect permanent / http://www.pypedia.org
    </VirtualHost>


    <VirtualHost *:80>
        ServerAdmin admin@pypedia.com
        DocumentRoot /var/www/pypedia
        ServerName www.pypedia.com

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined
    </VirtualHost>

    <VirtualHost *:80>
        ServerName pypedia.com
        Redirect permanent / http://www.pypedia.com
    </VirtualHost>
* Enable apache configuration:
    a2ensite pypedia
    a2dissite 000-default
    /etc/init.d/apache2 reload
* Set no MySQL password in LocalSettings.php
* Set endpoint of sandbox in pypedia.php and pypedia.js
* Chroot:
    apt-get -y install debootstrap 
    apt-get -y install schroot
    sudo debootstrap --variant=buildd --arch amd64 trusty /var/chroot/ http://archive.ubuntu.com/ubuntu
    mount -o bind /proc  /var/chroot/proc
    mount -o bind /dev  /var/chroot/dev
    mount -o bind /dev/pts  /var/chroot/dev/pts
    mount -o bind /sys  /var/chroot/sys
    chroot /var/chroot
* Inside chroot:
    adduser --disabled-password puser
    echo "none /dev/shm tmpfs rw,nosuid,nodev,noexec 0" >> /etc/fstab
    mount -a
    apt-get install wget
    apt-get install curl 
    apt-get install vim-tiny
    apt-get install libglib2.0-0 
    apt-get install libxext6 
    apt-get install libsm6
    apt-get install libxrender
* Install anaconda as puser
* Inside chroot:
    sed -i 's/^backend[ \t]*:.*$/backend : Agg/g' `python -c 'import matplotlib; print matplotlib.matplotlib_fname()'`
    sed -i 's/^backend[ \t]*:.*$/backend : Agg/g' `/home/puser/anaconda/bin/python -c 'import matplotlib; print matplotlib.matplotlib_fname()'`
* Configure sandbox port
* Run sandbox:
    su - puser
    wget https://raw.github.com/kantale/PyPedia_server/master/utils/pyp_sandbox2.py
    touch /home/puser/nohup.out
    chmod 622 /home/puser/nohup.out

    #As a puser
    #cd /home/puser; nohup python pyp_sandbox2.py &
* In /etc/rc.local:

    mount -o bind /proc  /var/chroot/proc
    mount -o bind /dev  /var/chroot/dev
    mount -o bind /dev/pts  /var/chroot/dev/pts
    mount -o bind /sys  /var/chroot/sys
    chroot /var/chroot mount -a
* In /var/chroot/root:
    root@pypedia:~# cat test_connection.sh
    #Check connection
    TEST=$(curl -sL  http://www.pypedia.com/index.php --data-urlencode "run_code=Hello_world()")
    TEST=$(echo $TEST | cut -d ' ' -f 1)

    date >> /root/test_connection.log

    if [ $TEST = 'Hello' ] ; then
            #Do nothing
            echo "it works" >> /root/test_connection.log
    else
            echo "it does not work" >> /root/test_connection.log
            echo "kill python.." >> /root/test_connection.log
            ps -ef | grep "sandbox" | awk '{print $2}' | xargs kill

            #Wait half a minute for the socket to be released
            echo "wait 30 seconds for the socket to be released" >> /root/test_connection.log 
            sleep 30

            #Restart as puser
            echo "restart as puser" >> /root/test_connection.log 
            su puser sh -c "cd; nohup /home/puser/anaconda/bin/python pyp_sandbox2.py &"
    fi
* In parent root:
    root@pypedia:~# crontab -l
    ...
    */1 * * * * /usr/sbin/chroot /var/chroot sh /root/test_connection.sh
