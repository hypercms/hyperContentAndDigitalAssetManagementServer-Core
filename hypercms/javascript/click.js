function hcms_noContextmenu(e) 
{
  if (!e) var e = window.event;

  return false;
} 

function hcms_noRightClick(e) 
{
  if (!e) var e = window.event;

  // left mouse click
  if (e.which == 2 || e.which == 3) 
  {
    return false;
  }
  else if (e.button == 2 || e.button == 3) 
  {
    return false;
  }
}

// initialize
document.oncontextmenu = hcms_noContextmenu;
document.onmousedown = hcms_noRightClick;
document.onmouseup = hcms_noRightClick;
