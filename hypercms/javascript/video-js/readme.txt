These changes need to be applied in the file video-js.css in order to center the play button:

.vjs-default-skin .vjs-big-play-button {

  /* Center it horizontally */
  left: 50%;
  margin-left: -1em;
  /* Center it vertically */
  top: 50%;
  margin-top: -1em;

  width: 2em;
  height: 2em;

}

.vjs-default-skin .vjs-big-play-button:before {


  line-height: 2em;

}