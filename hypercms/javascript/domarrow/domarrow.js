// offset from the center on y axis for arrows
var hcms_connection_topoffset = 0;

function hcms_connections_getNumberOrDef (val, def)
{
  return typeof val === 'number' && !isNaN(val) ? val : def;
}

function hcms_connections_isVisible (element)
{
  return element && element.style.visibility !== 'hidden';
}

function hcms_connections_inside (minX, minY, maxX, maxY, x1, y1)
{
  return minX <= x1 && x1 <= maxX && minY <= y1 && y1 <= maxY;
}

function hcms_connections_intersectionPoint (x1, y1, x2, y2, minX, minY, maxX, maxY)
{
  var min = Math.min, max = Math.max;
  var good = inside.bind(null, min(x1, x2), min(y1, y2), max(x1, x2), max(y1, y2));

  if ((x1 <= minX && x2 <= minX) || (y1 <= minY && y2 <= minY) || (x1 >= maxX && x2 >= maxX) || (y1 >= maxY && y2 >= maxY) || (inside(minX, minY, maxX, maxY, x1, y1) && inside(minX, minY, maxX, maxY, x2, y2)))
    return null;

  var m = (y2 - y1) / (x2 - x1);
  var y = m * (minX - x1) + y1;
  if (minY < y && y < maxY && good(minX, y)) return {x: minX, y:y};

  y = m * (maxX - x1) + y1;
  if (minY < y && y < maxY && good(maxX, y)) return {x: maxX, y:y};

  var x = (minY - y1) / m + x1;
  if (minX < x && x < maxX && good(x, minY)) return {x: x, y:minY};

  x = (maxY - y1) / m + x1;
  if (minX < x && x < maxX && good(x, maxY)) return {x: x, y:maxY};

  return null;
}

// adjust connection line
function hcms_connections_adjustLine (from, to, line, trafo)
{
  var color = trafo && trafo.color || 'black';
  var W = trafo && trafo.width || 2;

  var fromB = parseFloat(from.style.top) ? null : from.getBoundingClientRect();
  var toB = parseFloat(to.style.top) ? null : to.getBoundingClientRect();
  var fromBStartY = (fromB ? window.scrollY + fromB.top : parseFloat(from.style.top));
  var fromBStartX = (fromB ? window.scrollX + fromB.left : parseFloat(from.style.left));
  var toBStartY = (toB ? window.scrollY + toB.top : parseFloat(to.style.top));
  var toBStartX = (toB ? window.scrollX + toB.left : parseFloat(to.style.left));
  var fromBWidth = (fromB ? fromB.width : parseFloat(from.style.width) || from.offsetWidth);
  var fromBHeight = (fromB ? fromB.height : parseFloat(from.style.height) || from.offsetHeight);
  var toBWidth = (toB ? toB.width : parseFloat(to.style.width) || to.offsetWidth);
  var toBHeight = (toB ? toB.height : parseFloat(to.style.height) || to.offsetHeight);

  var fT = fromBStartY + fromBHeight * hcms_connections_getNumberOrDef(trafo && trafo.fromY, hcms_connections_getNumberOrDef(trafo, 0.5));
  var tT = toBStartY + toBHeight * hcms_connections_getNumberOrDef(trafo && trafo.toY, hcms_connections_getNumberOrDef(trafo, 0.5));
  var fL = fromBStartX + fromBWidth * hcms_connections_getNumberOrDef(trafo && trafo.fromX, hcms_connections_getNumberOrDef(trafo, 0.5));
  var tL = toBStartX + toBWidth * hcms_connections_getNumberOrDef(trafo && trafo.toX, hcms_connections_getNumberOrDef(trafo, 0.5));

  var CA   = Math.abs(tT - fT);
  var CO   = Math.abs(tL - fL);
  var H    = Math.sqrt(CA*CA + CO*CO);
  var ANG  = 180 / Math.PI * Math.acos( CO/H );

  if ((fT >= tT || fL >= tL) && (tT >= fT || tL >= fL))
  {
    ANG *= -1;
  }

  if (trafo && trafo.onlyVisible)
  {
    var arrangeFrom = hcms_connections_intersectionPoint(fL, fT, tL, tT, fromBStartX, fromBStartY, fromBStartX + fromBWidth, fromBStartY + fromBHeight);
    var arrangeTo = hcms_connections_intersectionPoint(fL, fT, tL, tT, toBStartX, toBStartY, toBStartX + toBWidth, toBStartY + toBHeight);

    if (arrangeFrom)
    {
      fL = arrangeFrom.x;
      fT = arrangeFrom.y;
    }

    if (arrangeTo)
    {
      tL = arrangeTo.x;
      tT = arrangeTo.y;
    }

    CA   = Math.abs(tT - fT);
    CO   = Math.abs(tL - fL);
    H    = Math.sqrt(CA*CA + CO*CO);
  }

  var top  = (tT+fT)/2 - W/2;
  var left = (tL+fL)/2 - H/2;

  var arrows  = [...line.getElementsByClassName('arrow')];

  var needSwap = (fL > tL || (fL == tL && fT < tT));
  var arrowFw = needSwap && hcms_connections_isVisible(arrows[0]) && arrows[0] || !needSwap && hcms_connections_isVisible(arrows[1]) && arrows[1];
  var arrowBw = !needSwap && hcms_connections_isVisible(arrows[0]) && arrows[0] || needSwap && hcms_connections_isVisible(arrows[1]) && arrows[1];

  if (arrowFw)
  {
    arrowFw.classList.remove('arrow-bw');
    arrowFw.classList.add('arrow-fw');
    arrowFw.style.borderRightColor = color;
    arrowFw.style.top = W/2-6 + "px";
  }

  if (arrowBw)
  {
    arrowBw.classList.remove('arrow-fw');
    arrowBw.classList.add('arrow-bw');
    arrowBw.style.borderLeftColor = color;
    arrowBw.style.top = W/2-6 + "px";
  }

  // new offset based on color
  if (color == "green")
  {
    left = left - 2;
    top = top - hcms_connection_topoffset;
  }
  else if (color == "red")
  {
    left = left + 2;
    top = top + hcms_connection_topoffset;
  }

  line.style.display = "none";
  line.style["-webkit-transform"] = 'rotate('+ ANG +'deg)';
  line.style["-moz-transform"] = 'rotate('+ ANG +'deg)';
  line.style["-ms-transform"] = 'rotate('+ ANG +'deg)';
  line.style["-o-transform"] = 'rotate('+ ANG +'deg)';
  line.style["-transform"] = 'rotate('+ ANG +'deg)';
  line.style.top    = top+'px';
  line.style.left   = left+'px';
  line.style.width  = H+'px';
  line.style.height = W+'px';
  line.style.backgroundColor = color; // for MS Edge
  line.style.background = "linear-gradient(to right, " +
    (arrowFw ? "transparent" : color) +" 11px, " +
    color + " 11px " + (H - 11) + "px, " +
    (arrowBw ? "transparent" : color) + " " + (H - 11) + "px 100%)";
  line.style.display = "initial";
}

// redraw single connection
function hcms_connections_repaintConnection (connectionElement)
{
  var fromQ = connectionElement.getAttribute('from');
  var fromE = document.querySelector(fromQ);
  var toQ = connectionElement.getAttribute('to');
  var toE = document.querySelector(toQ);
  var lineE = connectionElement.getElementsByClassName('line')[0];

  if (!lineE)
  {
    lineE = document.createElement('div');
    lineE.classList.add('line');
    connectionElement.appendChild(lineE);
  }
  var needTail = connectionElement.hasAttribute('tail');
  var needHead = connectionElement.hasAttribute('head');
  var arrows = lineE.getElementsByClassName('arrow');
  var tail = arrows[0];
  var head = arrows[1];

  if (!tail && (needHead || needTail))
  {
    tail = document.createElement('div');
    tail.classList.add('arrow');
    lineE.appendChild(tail);
  }

  if (!head && needHead)
  {
    head = document.createElement('div');
    head.classList.add('arrow');
    lineE.appendChild(head);
  }

  tail && (tail.style.visibility = needTail ? 'visible' : 'hidden');
  head && (head.style.visibility = needHead ? 'visible' : 'hidden');

  var textE = lineE.getElementsByClassName('text')[0];
  var textMessage = connectionElement.getAttribute('text');

  if (!textE && textMessage)
  {
    textE = document.createElement('div');
    textE.classList.add('text');
    lineE.appendChild(textE);
  }

  if (textE && textE.innerText != textMessage)
  {
    textE.innerText = textMessage;
  }

  hcms_connections_adjustLine(fromE, toE, lineE, {
    color: connectionElement.getAttribute('color'),
    onlyVisible: connectionElement.hasAttribute('onlyVisible'),
    fromX: parseFloat(connectionElement.getAttribute('fromX')),
    fromY: parseFloat(connectionElement.getAttribute('fromY')),
    toX: parseFloat(connectionElement.getAttribute('toX')),
    toY: parseFloat(connectionElement.getAttribute('toY')),
    width: parseFloat(connectionElement.getAttribute('width'))
  });
}

// redraw connections based on the affected connection id (id includes from or to)
function hcms_connections_repaintConnections (connection_id)
{
  if (connection_id != '')
  {
    var connections = document.getElementsByTagName('connection');

    if (connections.length > 0)
    {
      for (var i=0; i<connections.length; i++)
      {
        if (connections[i].id.indexOf(connection_id) > -1)
        {
          var change = document.getElementById(connections[i].id);
          hcms_connections_repaintConnection (change);
        }
      }
    }
  }
}

// create single connection
function hcms_connections_createOne (newElement)
{
  hcms_connection_connectionElements.push(newElement);
  hcms_connections_repaintConnection (newElement);
}

// create all connections
function hcms_connections_create ()
{
  [...document.body.getElementsByTagName('connection')].forEach(hcms_connections_createOne);
}

// remove single connection
function hcms_connections_removeConnection (tag)
{
  for (var i = hcms_connection_connectionElements.length - 1; i >= 0; i--)
  {
    if (hcms_connection_connectionElements[i] === tag)
      hcms_connection_connectionElements.splice(i, 1);
  }
}

function hcms_connections_bodyNewElement (changes)
{
  changes.forEach(e => {
    e.removedNodes.forEach(n => {
      if (n.tagName && n.tagName.toLowerCase() === 'connection')
      hcms_connections_removeConnection(n);
    });
    e.addedNodes.forEach(n => {
      if (n.tagName && n.tagName.toLowerCase() === 'connection')
      hcms_connections_createOne(n);
    });
  });
}

// initalize
var hcms_connection_connectionElements = [];
document.body && hcms_connections_create() || window.addEventListener("load", hcms_connections_create);



