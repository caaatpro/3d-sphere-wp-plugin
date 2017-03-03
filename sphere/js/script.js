var taga,
  xyz = Array(),
  num_pnt,
  za = 0,
  ADA = (Math.PI / 180),
  ada = ADA,
  _cnt = 12,
  OB = 0.15,
  FB = 8, // font-size
  FE = 12, // font-size
  CT = 150-FE,
  CL = 150-FE,
  CZ = 150-FE,
  _Rad = 150-FE,
  to = null,
  TMR = 1000,
  state = 0, //0 -idle, 1-go to
  tmr = 0,
  ttmr = 0,
  zax = 0,
  zay = 0,
  zaz = 0,
  lto = null;

function did(el) {
  return document.getElementById(el);
}

function setStyles(el, styles) {
  for (var x in styles) {
    el.style[x] = styles[x];
  }
}

function initSphere(numpnt, radius) {
  var x, y, z, f, t, c;
  num_pnt = numpnt;

  for (n = 1; n < numpnt + 1; n++) {
    f = Math.acos(-1 + ((2 * n) - 1) / numpnt);
    t = Math.sqrt(numpnt * Math.PI) * f;


    x = Math.round(radius * Math.cos(t) * Math.sin(f));
    y = Math.round(radius * Math.sin(t) * Math.sin(f));
    z = Math.round(radius * Math.cos(f));

    xyz[n] = {
      x: x,
      y: y,
      z: z
    };

    var el = document.createElement('div');
    el.id = "pnt" + n;
    el.innerHTML = "<span>" + taga[n - 1].name + "</span> <span class='num'> " + taga[n - 1].num + "</span>";
    el.n = n;
    el.className = "sptag";
    left = (CL + x - el.style.width / 2);
    top = (CT + y);
    el.dataset.top = top;
    el.dataset.left = left;
    setStyles(el, {
      transform: "translate(" + left + "px, " + top + "px)",
      zIndex: z
    });
    el.onmouseover = function() {
      mouseover(this);
    };
    el.onmouseout = function() {
      mouseout(this)
    };
    document.getElementById('sphere').appendChild(el);

  }

  state = 0;
  rotateth();
}

function rotate(n, a, b, c) {
  var x, y, z;
  x = xyz[n].x;
  y = xyz[n].y;
  z = xyz[n].z;

  var sa = Math.sin(a),
    ca = Math.cos(a),
    sb = Math.sin(b),
    cb = Math.cos(b),
    sc = Math.sin(c),
    cc = Math.cos(c);
  var ox = x,
    oy = y,
    oz = z;
  x = ox * cb * cc - oy * cb * sc + oz * sb;
  y = ox * (cc * sa * sb + ca * sc) + oy * (ca * cc - sa * sb * sc) - oz * (cb * sa);
  z = ox * (sa * sc - ca * cc * sb) + oy * (cc * sa + ca * sb * sc) + oz * (ca * cb);

  xyz[n] = {
    x: x,
    y: y,
    z: z
  };
  var el = did("pnt" + n);
  if (el) {
    x = Math.round(x);
    y = Math.round(y);
    z = Math.round(z);
    var o = (z + _Rad) / (2 * _Rad);
    var fs = FB + o * FE;
    o += OB;

    var left = x + CL - el.style.width / 2;
    var top = y + CT;
    el.dataset.top = top;
    el.dataset.left = left;
    setStyles(el, {
      transform: "translate(" + left + "px, " + top + "px)",
      zIndex: (CZ + z) * 2,
      fontSize: fs + "px"
    });
    setStyles(el, {
      opacity: o
    });
  }
}

function rotateth() {

  switch (state) {
    case 0:
      if (tmr > 0) tmr--;
      else {
        ada = 0.0001 + ADA / 2;
        tmr = TMR;
      }
      dax = day = daz = ada;
      break;
    case 1:
      ada = ADA;
      if (tmr >= 100) {
        state = 2;
        to = setTimeout(function() {
          state = 0;
          tmr = 0;
          ada = ADA;
          rotateth();
        }, 5000);
        return;
      }
      tmr++;
      break;
  }

  for (i = 1; i <= _cnt; i++)
    rotate(i, dax, day, daz);

  lto = setTimeout(function() {
    rotateth();
  }, 50);
}


function mouseover(el) {
  var ll = (CL - parseInt(el.dataset.left)) / _Rad,
    lt = (parseInt(el.dataset.top) - CT) / _Rad;
  daz = 0;
  dax = (ADA / 2) * lt;
  day = (ADA / 2) * ll;

  tmr = 0;
  ttmr = 0;
  clearTimeout(to);
  clearTimeout(lto);
  lto = null;
  to = null;
  setTimeout(function() {
    rotateth();
  }, 50);
  state = 1;
}

function mouseout(el) {

}

onload = function() {
  if (taga) {
    _cnt = taga.length;
    setTimeout(function() {
      initSphere(_cnt, _Rad);
    }, 100);
  }
}
