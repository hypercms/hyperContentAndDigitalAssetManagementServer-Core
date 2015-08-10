These changes need to be applied in the file video-js.css in order to center the play button:

.vjs-default-skin .vjs-big-play-button {

  /* Center it horizontally */
  left: 50%;
  margin-left: -1em;
  /* Center it vertically */
  top: 50%;
  margin-top: -0.9em;

  width: 1.6em;
  height: 1.6em;

}

.vjs-default-skin .vjs-big-play-button:before {

  line-height: 1.6em;

}