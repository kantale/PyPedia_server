
import time
from sandbox import Sandbox



def func1(a,b):
	print "Output:", a+b


def func2():
	while True:
		time.sleep(1)


def func3():
	a = []
	count = 0
	while True:
		count += 1
		a += [range(100000000)]
		

sandbox = Sandbox()
#print sandbox.call(func1, 1, 2)
#print sandbox.call(func2)
print sandbox.call(func3)

