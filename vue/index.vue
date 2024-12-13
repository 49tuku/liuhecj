<template>
	<view class="content">
		<view class="liveno" v-show="isLive!=3">
			<text class="title" v-show="isLive==1">十二生肖搅珠直播时间为开奖日 <text style="color: red;">{{time1}}-{{time2}}</text> (北京时间)</text>
			<cover-image v-show="isLive==2" src="../../static/play.png" class="playicon" @click="play(true)"></cover-image>
		</view>
		<viw class="player" v-show="isLive==3">
			<video class="video" ref="vv" :src="url" :show-center-play-btn="false" :show-play-btn="false" :show-fullscreen-btn="false" :show-progress="false" :initial-time="initialtime" :controls="false" @play="play"></video>
		</viw>
	</view>
</template>

<script>
	//https://spwz.s3.ap-southeast-1.amazonaws.com/262024234.mp4
	export default {
		data() {
			return {
				isLive:1, //1没有直播；2可直播；3播放中
				url:'',
				initialtime:0,
				time1:'22:31',
				time2:'22:33',
			}
		},
		onLoad(res) {
			this.url = res.url;
			this.play(0)
		},
		methods: {
			play(tplay){
				var nowTime = new Date();
				let mm = nowTime.getMonth()+1;
				if(mm < 10){
					mm = '0'+mm;
				}
				let dd = nowTime.getDate();
				if(dd < 10){
					dd = '0'+dd;
				}
				//开始时间
				var startStr = nowTime.getFullYear() +'-'+mm +'-'+ dd + ' '+ this.time1;
				var endStr = nowTime.getFullYear() +'-'+mm +'-'+ dd + ' '+ this.time2;
				var startime = (new Date(startStr)).getTime(); //直播开始时间-毫秒
				var endtime = (new Date(endStr)).getTime();    //直播结束时间-毫秒
				//当前时间
				var nT = nowTime.getTime();
				if(nT >= startime && nT < endtime){
					//正在直播
					this.isLive = 2;
					//播放指定时间-s
					this.initialtime = Math.ceil((nT - startime)/1000);
					console.log('播放'+this.initialtime)
					if(tplay && this.initialtime < 116){
						this.$refs.vv.play()
						this.isLive = 3;
					}
					
				}else{
					this.isLive = 1;
				}
				
			}
		}
	}
</script>

<style>
uni-page{background-color: #000;}
.liveno{max-width: 600px; margin: 0px auto; background-color: #000;padding: 130px 0; }
.liveno .title{color: #fff; font-size: 15px; display: block; text-align: center;}
.video{width: 100%; height: 500px;}
.playicon{width: 60px; height: 60px; position: absolute; top: 200px; left: 0; right: 0; margin: 0px auto;}
@media screen and (max-width: 720px) {
	.video{width: 100%; height: 400px;}
	.playicon{top: 140px; }
}


@media screen and (max-width: 420px) {
	.liveno{padding: 100px 0; font-size: 12px;}
	.liveno .title{font-size: 12px;}
	.video{width: 100%; height: 250px;}
	.playicon{top: 80px; }
}

</style>
