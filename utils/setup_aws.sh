echo "pypuserpw
pypuserpw





Y
" | sudo adduser pypuser

sudo sed -i 's/PasswordAuthentication no/PasswordAuthentication yes/g' /etc/ssh/sshd_config

sudo /etc/init.d/ssh reload

sudo reload ssh

sudo apt-get -y install git

sudo mkdir /home/pypuser/run
cd /home/pypuser/run; sudo git clone git://github.com/kantale/pypedia.git
cd /home/pypuser/run; sudo wget https://raw.github.com/kantale/PyPedia_server/master/utils/ssh_pyp_client.py
cd 

sudo chown pypuser /home/pypuser/run/pypedia/pypCode/

wget http://pypi.python.org/packages/source/s/setuptools/setuptools-0.6c11.tar.gz#md5=7df2a529a074f613b509fb44feefe74e
tar zxvf setuptools-0.6c11.tar.gz
cd /home/ubuntu/setuptools-0.6c11/; python setup.py build
cd /home/ubuntu/setuptools-0.6c11/; sudo python setup.py install
cd

sudo easy_install nose

sudo apt-get -y install gcc
sudo apt-get -y install python-dev
sudo apt-get -y install gfortran
git clone git://github.com/numpy/numpy.git numpy
python setup.py build --fcompiler=gnu95
sudo python setup.py install

sudo apt-get -y install libatlas-base-dev
sudo apt-get -y install g++
git clone git://github.com/scipy/scipy.git scipy
cd /home/ubuntu/scipy; python setup.py build
cd /home/ubuntu/scipy; sudo python setup.py install
cd

wget biopython-1.59.tar.gz
tar zxvf biopython-1.59.tar.gz 
cd /home/ubuntu/biopython-1.59; python setup.py build
cd /home/ubuntu/biopython-1.59; sudo python setup.py install
cd
