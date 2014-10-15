function PrintStatus()
{
  window.status = "hyper Content Management Server";
  setTimeout('PrintStatus();', 1000);
}

PrintStatus();