import numpy as np, subprocess, sys
SR=48000
vo=np.frombuffer(subprocess.run(['ffmpeg','-v','error','-i','vo.mp3','-ac','1','-ar',str(SR),'-f','f32le','-'],capture_output=True).stdout,dtype=np.float32).astype(np.float64)
N=len(vo); T=N/SR; t=np.arange(N)/SR
rng=np.random.default_rng(7)
mus=np.zeros(N); sfx=np.zeros(N)
def add(buf,start,sig,g):
    i=int(start*SR); j=min(N,i+len(sig))
    if i<N: buf[i:j]+=g*sig[:j-i]
# --- music: 100 BPM dark pulse ---
bpm=100; beat=60/bpm
def kick():
    d=np.arange(int(.35*SR))/SR; f=55+90*np.exp(-d*30); return np.sin(2*np.pi*np.cumsum(f)/SR)*np.exp(-d*9)
def hat():
    d=np.arange(int(.06*SR))/SR; n=rng.standard_normal(len(d)); n=np.diff(n,prepend=0); return n*np.exp(-d*70)*.3
k=kick();h=hat()
b=0.0;i=0
while b<T-0.3:
    if i%2==0 or i%8==7: add(mus,b,k,.9)
    add(mus,b+beat/2,h,.5); i+=1; b+=beat
# pad: minor chord with slow swell
for f,g in [(55,.5),(110,.25),(130.8,.18),(164.8,.15),(220,.08)]:
    mus+=g*np.sin(2*np.pi*f*t+np.sin(2*np.pi*.2*t)*.6)*.25
env=np.clip(t/1.0,0,1)*np.clip((T-t)/1.5,0,1)
mus*=env
# --- sfx ---
def whoosh(d=.45,up=True):
    n=rng.standard_normal(int(d*SR)); x=np.linspace(0,1,len(n)); 
    from numpy.fft import rfft,irfft
    e=np.sin(np.pi*x)**2; # simple band shaping via moving average
    k=np.ones(12)/12; n=np.convolve(n,k,'same')-np.convolve(n,np.ones(60)/60,'same')
    return n*e*1.8
def impact():
    d=np.arange(int(.9*SR))/SR; f=70*np.exp(-d*4)+35; s=np.sin(2*np.pi*np.cumsum(f)/SR)*np.exp(-d*5)
    n=rng.standard_normal(len(d))*np.exp(-d*25)*.4; return s+n
def sparkle():
    d=np.arange(int(.8*SR))/SR; s=np.zeros(len(d))
    for j,f in enumerate([2093,2637,3136,4186]): s+=np.sin(2*np.pi*f*d)*np.exp(-np.clip(d-j*.06,0,None)*9)*(d>=j*.06)
    return s*.25
def riser(d=1.0):
    x=np.arange(int(d*SR))/SR; f=200+1400*(x/d)**2; n=rng.standard_normal(len(x))
    return (np.sin(2*np.pi*np.cumsum(f)/SR)*.3+np.convolve(n,np.ones(8)/8,'same')*.5)*(x/d)**2
def ding():
    d=np.arange(int(1.2*SR))/SR; return (np.sin(2*np.pi*1318.5*d)+.5*np.sin(2*np.pi*1975.5*d))*np.exp(-d*4)*.5
for c in [1.78,5.68,8.2,13.5,16.8,17.96,22.0,26.2,31.2]: add(sfx,c-0.2,whoosh(),.22)
for s in [0.9,6.86,18.96,27.96,31.34]: add(sfx,s,impact(),.55)
for s in [0.92,6.86,18.96,28.6,31.34]: add(sfx,s,sparkle(),.35)
add(sfx,5.9,riser(.95),.25); add(sfx,17.0,riser(.95),.2)
for s in [13.74,15.02,15.48,31.5,31.68,31.86,32.04]: add(sfx,s,whoosh(.25),.12)
add(sfx,20.5,ding(),.3); add(sfx,28.4,ding()[:SR//3],.25)
# truck engine rumble 22.2-23.6
d=np.arange(int(1.6*SR))/SR; add(sfx,22.2,np.sin(2*np.pi*48*d+3*np.sin(2*np.pi*9*d))*np.exp(-np.clip(d-1.1,0,None)*6)*.5,.3)
# duck music under voice
a=np.abs(vo); w=int(.12*SR); envv=np.convolve(a,np.ones(w)/w,'same'); envv=envv/envv.max()
duck=1-0.55*np.clip(envv*4,0,1)
mus=mus/np.abs(mus).max()*0.16*duck
sfx=sfx/np.abs(sfx).max()*0.38
mix=vo/np.abs(vo).max()*0.85+mus+sfx
mix=np.tanh(mix*1.1)/1.1
mix.astype(np.float32).tofile('mix.f32'); print(T,len(mix))
