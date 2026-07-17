(function () {
  const wrappers = document.querySelectorAll(".idr-wrap[data-flight-api]");
  const modeCopy = {
    field: {
      title: "Field proof view",
      copy: "Visible planes, damage markers, and a clean contractor handoff.",
    },
    contractor: {
      title: "Contractor capture view",
      copy: "Waypoints, camera pitch, slope IDs, and repeatable photo capture order.",
    },
    carrier: {
      title: "Carrier packet view",
      copy: "Damage findings tied to photo IDs, severity, slope labels, and reviewer notes.",
    },
  };
  const preflightLabels = {
    airspace: "FAA/airspace, TFR, and local rules checked",
    weather: "Wind, rain, visibility, and daylight checked",
    battery: "Aircraft/controller batteries and storage checked",
    rth: "Return-to-home altitude and home point checked",
    gps: "GPS lock, compass, and map position checked",
    obstacles: "Wires, trees, chimney, people, vehicles, and neighbors checked",
    vlos: "VLOS, spotter plan, and launch/landing zone checked",
    pilot: "Licensed pilot approved final route inside the flight app",
  };

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function setMode(wrapper, mode) {
    const next = modeCopy[mode] ? mode : "field";
    const title = wrapper.querySelector("[data-idr-mode-title]");
    const copy = wrapper.querySelector("[data-idr-mode-copy]");
    wrapper.dataset.idrMode = next;

    wrapper.querySelectorAll("[data-idr-mode]").forEach((button) => {
      button.classList.toggle("is-active", button.getAttribute("data-idr-mode") === next);
    });

    if (title) title.textContent = modeCopy[next].title;
    if (copy) copy.textContent = modeCopy[next].copy;
  }

  function readConfig(wrapper) {
    const globalConfig = window.InspectorDroneProof || {};
    const data = (wrapper && wrapper.dataset) || {};
    return {
      geocodeApi: globalConfig.geocodeApi || data.geocodeApi || "",
      roofDataApi: globalConfig.roofDataApi || data.roofDataApi || "",
      roofDataSaveApi: globalConfig.roofDataSaveApi || data.roofDataSaveApi || "",
      fieldJobApi: globalConfig.fieldJobApi || data.fieldJobApi || "",
      fieldJobLatestApi: globalConfig.fieldJobLatestApi || data.fieldJobLatestApi || "",
      fieldPhotoApi: globalConfig.fieldPhotoApi || data.fieldPhotoApi || "",
      fieldReportApi: globalConfig.fieldReportApi || data.fieldReportApi || "",
      aiQaApi: globalConfig.aiQaApi || data.aiQaApi || "",
      restNonce: globalConfig.restNonce || data.restNonce || "",
      googleConfigured: Boolean(globalConfig.googleConfigured || data.geocodeApi),
      openAiConfigured: Boolean(globalConfig.openAiConfigured || data.aiQaApi),
      sampleHouse: globalConfig.sampleHouse || data.sampleHouse || "",
      djiSdkKit: globalConfig.djiSdkKit || data.djiSdkKit || "",
      playAppId: globalConfig.playAppId || data.playAppId || "",
      playPackageName: globalConfig.playPackageName || data.playPackageName || "",
      playStoreUrl: globalConfig.playStoreUrl || data.playStoreUrl || "",
    };
  }

  function setupModes(wrapper) {
    wrapper.querySelectorAll("[data-idr-mode]").forEach((button) => {
      button.addEventListener("click", () => setMode(wrapper, button.getAttribute("data-idr-mode")));
    });
    setMode(wrapper, "field");
  }

  function fitCanvas(canvas) {
    const rect = canvas.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;
    const width = Math.max(760, Math.floor(rect.width || canvas.width || 1100));
    const height = Math.max(520, Math.floor(rect.height || canvas.height || 650));
    const deviceWidth = Math.floor(width * ratio);
    const deviceHeight = Math.floor(height * ratio);

    if (canvas.width !== deviceWidth || canvas.height !== deviceHeight) {
      canvas.width = deviceWidth;
      canvas.height = deviceHeight;
    }

    return { ratio, width, height };
  }

  function roundedRect(ctx, x, y, width, height, radius) {
    const r = Math.min(radius, width / 2, height / 2);
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + width - r, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + r);
    ctx.lineTo(x + width, y + height - r);
    ctx.quadraticCurveTo(x + width, y + height, x + width - r, y + height);
    ctx.lineTo(x + r, y + height);
    ctx.quadraticCurveTo(x, y + height, x, y + height - r);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
  }

  function drawBackground(ctx, width, height) {
    const bg = ctx.createLinearGradient(0, 0, width, height);
    bg.addColorStop(0, "#04111e");
    bg.addColorStop(0.45, "#0a2d4a");
    bg.addColorStop(1, "#04111e");
    ctx.fillStyle = bg;
    ctx.fillRect(0, 0, width, height);

    ctx.save();
    ctx.strokeStyle = "rgba(107,181,231,0.13)";
    ctx.lineWidth = 1;
    for (let x = -width; x < width * 2; x += 54) {
      ctx.beginPath();
      ctx.moveTo(x, height * 0.12);
      ctx.lineTo(x + width * 0.55, height);
      ctx.stroke();
    }
    for (let x = -width * 0.4; x < width * 1.5; x += 54) {
      ctx.beginPath();
      ctx.moveTo(x, height);
      ctx.lineTo(x + width * 0.55, height * 0.12);
      ctx.stroke();
    }
    ctx.restore();

    ctx.save();
    ctx.strokeStyle = "rgba(255,255,255,0.08)";
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(width * 0.05, height * 0.78);
    ctx.lineTo(width * 0.30, height * 0.64);
    ctx.lineTo(width * 0.52, height * 0.78);
    ctx.lineTo(width * 0.78, height * 0.58);
    ctx.lineTo(width * 0.96, height * 0.68);
    ctx.stroke();
    ctx.restore();
  }

  function createProjector(width, height, time) {
    const yaw = -0.72 + Math.sin(time / 5800) * 0.12;
    const scale = Math.min(width / 960, height / 700);
    const originX = width * 0.52;
    const originY = height * 0.58;
    const cos = Math.cos(yaw);
    const sin = Math.sin(yaw);

    return function project(point) {
      const x = point[0] * cos - point[1] * sin;
      const y = point[0] * sin + point[1] * cos;
      const z = point[2] || 0;
      return [
        originX + x * 0.88 * scale,
        originY + y * 0.36 * scale - z * 0.82 * scale,
        z,
      ];
    };
  }

  function screenPoints(project, points) {
    return points.map((point) => project(point));
  }

  function pathBounds(points) {
    return points.reduce((box, point) => {
      return {
        minX: Math.min(box.minX, point[0]),
        minY: Math.min(box.minY, point[1]),
        maxX: Math.max(box.maxX, point[0]),
        maxY: Math.max(box.maxY, point[1]),
      };
    }, { minX: Infinity, minY: Infinity, maxX: -Infinity, maxY: -Infinity });
  }

  function trace(ctx, points) {
    ctx.beginPath();
    points.forEach((point, index) => {
      if (index === 0) ctx.moveTo(point[0], point[1]);
      else ctx.lineTo(point[0], point[1]);
    });
    ctx.closePath();
  }

  function drawFace(ctx, project, points, fill, stroke) {
    const projected = screenPoints(project, points);
    trace(ctx, projected);
    ctx.fillStyle = fill;
    ctx.fill();
    if (stroke) {
      ctx.strokeStyle = stroke;
      ctx.lineWidth = 2;
      ctx.stroke();
    }
    return projected;
  }

  function drawSidedFace(ctx, project, points, fill, stroke, sidingColor) {
    const projected = drawFace(ctx, project, points, fill, stroke);
    const box = pathBounds(projected);
    ctx.save();
    trace(ctx, projected);
    ctx.clip();
    ctx.strokeStyle = sidingColor || "rgba(255,255,255,0.22)";
    ctx.lineWidth = 1;
    for (let y = box.minY + 10; y < box.maxY; y += 10) {
      ctx.beginPath();
      ctx.moveTo(box.minX - 20, y);
      ctx.lineTo(box.maxX + 20, y - 5);
      ctx.stroke();
    }
    ctx.restore();
    return projected;
  }

  function drawProjectedRect(ctx, project, point, width, height, fill, stroke, radius) {
    const p = project(point);
    ctx.save();
    ctx.fillStyle = fill;
    ctx.strokeStyle = stroke;
    ctx.lineWidth = 1.4;
    roundedRect(ctx, p[0] - width / 2, p[1] - height / 2, width, height, radius || 3);
    ctx.fill();
    ctx.stroke();
    ctx.restore();
    return p;
  }

  function drawWindowUnit(ctx, project, point, size, shutters) {
    const p = drawProjectedRect(ctx, project, point, size * 1.18, size, "#f4fbff", "rgba(7,24,39,0.34)", 3);
    ctx.save();
    ctx.strokeStyle = "rgba(15,117,188,0.7)";
    ctx.lineWidth = 1.4;
    ctx.beginPath();
    ctx.moveTo(p[0], p[1] - size * 0.5);
    ctx.lineTo(p[0], p[1] + size * 0.5);
    ctx.moveTo(p[0] - size * 0.59, p[1]);
    ctx.lineTo(p[0] + size * 0.59, p[1]);
    ctx.stroke();
    if (shutters) {
      ctx.fillStyle = "#071827";
      roundedRect(ctx, p[0] - size * 0.78, p[1] - size * 0.5, size * 0.14, size, 2);
      ctx.fill();
      roundedRect(ctx, p[0] + size * 0.64, p[1] - size * 0.5, size * 0.14, size, 2);
      ctx.fill();
    }
    ctx.restore();
  }

  function drawDoorUnit(ctx, project, point) {
    const p = drawProjectedRect(ctx, project, point, 42, 70, "#6b3f24", "rgba(255,255,255,0.5)", 4);
    ctx.save();
    ctx.fillStyle = "#f7dba7";
    ctx.beginPath();
    ctx.arc(p[0] + 13, p[1] + 4, 3, 0, Math.PI * 2);
    ctx.fill();
    ctx.strokeStyle = "rgba(255,255,255,0.34)";
    ctx.strokeRect(p[0] - 13, p[1] - 26, 26, 24);
    ctx.restore();
  }

  function drawGarageUnit(ctx, project, point) {
    const p = drawProjectedRect(ctx, project, point, 96, 54, "#8b5630", "rgba(255,255,255,0.55)", 4);
    ctx.save();
    ctx.strokeStyle = "rgba(255,255,255,0.38)";
    ctx.lineWidth = 1;
    for (let y = p[1] - 18; y <= p[1] + 18; y += 12) {
      ctx.beginPath();
      ctx.moveTo(p[0] - 44, y);
      ctx.lineTo(p[0] + 44, y);
      ctx.stroke();
    }
    for (let x = p[0] - 24; x <= p[0] + 24; x += 24) {
      ctx.beginPath();
      ctx.moveTo(x, p[1] - 26);
      ctx.lineTo(x, p[1] + 26);
      ctx.stroke();
    }
    ctx.restore();
  }

  function drawHouseShell(ctx, project) {
    const yard = screenPoints(project, [[-520, 308, -158], [520, 260, -158], [610, 420, -160], [-460, 490, -160]]);
    trace(ctx, yard);
    const yardFill = ctx.createLinearGradient(0, pathBounds(yard).minY, 0, pathBounds(yard).maxY);
    yardFill.addColorStop(0, "rgba(62,113,64,0.26)");
    yardFill.addColorStop(1, "rgba(98,150,72,0.54)");
    ctx.fillStyle = yardFill;
    ctx.fill();

    const drive = screenPoints(project, [[132, 216, -148], [414, 118, -146], [560, 236, -150], [236, 382, -154]]);
    trace(ctx, drive);
    ctx.fillStyle = "rgba(230,236,242,0.78)";
    ctx.fill();
    ctx.strokeStyle = "rgba(255,255,255,0.34)";
    ctx.stroke();

    const walkway = screenPoints(project, [[-116, 176, -149], [-52, 180, -149], [-118, 408, -154], [-210, 408, -154]]);
    trace(ctx, walkway);
    ctx.fillStyle = "rgba(226,232,238,0.7)";
    ctx.fill();

    const walls = [
      {
        fill: "#cfd9e1",
        stroke: "rgba(255,255,255,0.72)",
        siding: "rgba(255,255,255,0.3)",
        points: [[-344, 74, -146], [56, 126, -146], [56, 84, 4], [-344, 34, 4]],
      },
      {
        fill: "#aebdca",
        stroke: "rgba(255,255,255,0.54)",
        siding: "rgba(255,255,255,0.22)",
        points: [[56, 126, -146], [344, 22, -146], [330, -28, 0], [56, 84, 4]],
      },
      {
        fill: "#d8e1e8",
        stroke: "rgba(255,255,255,0.62)",
        siding: "rgba(7,24,39,0.08)",
        points: [[22, 222, -138], [386, 126, -138], [364, 102, -4], [22, 136, -2]],
      },
    ];

    ctx.save();
    ctx.shadowColor = "rgba(0,0,0,0.36)";
    ctx.shadowBlur = 24;
    ctx.shadowOffsetY = 18;
    walls.forEach((wall) => drawSidedFace(ctx, project, wall.points, wall.fill, wall.stroke, wall.siding));
    ctx.restore();

    const gableA = screenPoints(project, [[-308, 36, 4], [-166, -42, 94], [-24, 72, 4]]);
    trace(ctx, gableA);
    ctx.fillStyle = "#dce5ec";
    ctx.fill();
    ctx.strokeStyle = "rgba(255,255,255,0.7)";
    ctx.stroke();

    const gableB = screenPoints(project, [[86, 106, 0], [208, 18, 78], [340, 104, -4]]);
    trace(ctx, gableB);
    ctx.fillStyle = "#c5d1dc";
    ctx.fill();
    ctx.strokeStyle = "rgba(255,255,255,0.58)";
    ctx.stroke();

    drawWindowUnit(ctx, project, [-268, 66, -64], 38, true);
    drawWindowUnit(ctx, project, [-166, 82, -62], 38, true);
    drawDoorUnit(ctx, project, [-72, 92, -54]);
    drawWindowUnit(ctx, project, [126, 104, -62], 34, true);
    drawWindowUnit(ctx, project, [220, 82, -60], 34, true);
    drawGarageUnit(ctx, project, [284, 144, -64]);

    [[-106, 124, -112], [-28, 130, -110], [52, 128, -112]].forEach((point) => {
      const top = project([point[0], point[1], -20]);
      const bottom = project(point);
      ctx.save();
      ctx.strokeStyle = "#ffffff";
      ctx.lineWidth = 5;
      ctx.beginPath();
      ctx.moveTo(top[0], top[1]);
      ctx.lineTo(bottom[0], bottom[1]);
      ctx.stroke();
      ctx.restore();
    });

    const porch = screenPoints(project, [[-128, 132, -116], [72, 150, -116], [56, 178, -138], [-154, 158, -138]]);
    trace(ctx, porch);
    ctx.fillStyle = "rgba(255,255,255,0.68)";
    ctx.fill();
  }

  function drawShingles(ctx, points, color) {
    const box = pathBounds(points);
    ctx.save();
    trace(ctx, points);
    ctx.clip();
    ctx.strokeStyle = color;
    ctx.lineWidth = 1;
    for (let y = box.minY - 80; y <= box.maxY + 80; y += 18) {
      ctx.beginPath();
      ctx.moveTo(box.minX - 80, y);
      ctx.lineTo(box.maxX + 80, y - 34);
      ctx.stroke();
    }
    for (let x = box.minX - 80; x <= box.maxX + 80; x += 34) {
      ctx.beginPath();
      ctx.moveTo(x, box.minY - 30);
      ctx.lineTo(x + 28, box.maxY + 50);
      ctx.stroke();
    }
    ctx.restore();
  }

  function drawRoofModel(ctx, project) {
    const depth = 34;
    const planes = [
      {
        name: "Plane A",
        fillA: "#53606b",
        fillB: "#17202a",
        side: "#101822",
        accent: "#ed1b24",
        points: [[-372, 24, 14], [-166, -138, 116], [72, 66, 14], [-142, 144, 10]],
        label: [-202, -48, 116],
      },
      {
        name: "Plane B",
        fillA: "#414f5c",
        fillB: "#111923",
        side: "#0a1118",
        accent: "#0f75bc",
        points: [[-166, -138, 116], [78, -206, 102], [372, -24, 10], [72, 66, 14]],
        label: [94, -118, 104],
      },
      {
        name: "Plane C",
        fillA: "#596774",
        fillB: "#1b2630",
        side: "#111a22",
        accent: "#6bb5e7",
        points: [[24, 132, 0], [208, 14, 78], [382, 118, 4], [214, 218, -4]],
        label: [224, 82, 72],
      },
      {
        name: "Plane D",
        fillA: "#4a5964",
        fillB: "#141d26",
        side: "#111820",
        accent: "#ed1b24",
        points: [[-146, 132, 2], [-58, 58, 52], [74, 144, 2], [22, 184, -6]],
        label: [-48, 104, 50],
      },
    ];

    ctx.save();
    ctx.shadowColor = "rgba(0,0,0,0.42)";
    ctx.shadowBlur = 34;
    ctx.shadowOffsetY = 26;

    planes.forEach((plane) => {
      for (let i = 0; i < plane.points.length; i += 1) {
        const a = plane.points[i];
        const b = plane.points[(i + 1) % plane.points.length];
        const side = [
          a,
          b,
          [b[0], b[1], (b[2] || 0) - depth],
          [a[0], a[1], (a[2] || 0) - depth],
        ];
        drawFace(ctx, project, side, plane.side, "rgba(255,255,255,0.16)");
      }
    });

    planes.forEach((plane) => {
      const projected = screenPoints(project, plane.points);
      const box = pathBounds(projected);
      const fill = ctx.createLinearGradient(box.minX, box.minY, box.maxX, box.maxY);
      fill.addColorStop(0, plane.fillA);
      fill.addColorStop(1, plane.fillB);
      trace(ctx, projected);
      ctx.fillStyle = fill;
      ctx.fill();
      ctx.strokeStyle = "rgba(255,255,255,0.92)";
      ctx.lineWidth = 3;
      ctx.stroke();
      drawShingles(ctx, projected, "rgba(255,255,255,0.12)");
      trace(ctx, projected);
      ctx.strokeStyle = plane.accent;
      ctx.globalAlpha = 0.48;
      ctx.lineWidth = 2;
      ctx.stroke();
      ctx.globalAlpha = 1;
    });

    ctx.restore();

    const ridgeLines = [
      [[-166, -138, 122], [78, -206, 108]],
      [[-166, -138, 120], [72, 66, 20]],
      [[208, 14, 82], [214, 218, 0]],
    ];
    ctx.save();
    ctx.strokeStyle = "rgba(255,255,255,0.66)";
    ctx.lineWidth = 3;
    ridgeLines.forEach((line) => {
      const p = screenPoints(project, line);
      ctx.beginPath();
      ctx.moveTo(p[0][0], p[0][1]);
      ctx.lineTo(p[1][0], p[1][1]);
      ctx.stroke();
    });
    ctx.restore();

    const chimney = [
      [[210, -72, 18], [244, -88, 18], [244, -88, 116], [210, -72, 116]],
      [[244, -88, 18], [268, -66, 18], [268, -66, 116], [244, -88, 116]],
      [[210, -72, 116], [244, -88, 116], [268, -66, 116], [232, -50, 116]],
    ];
    chimney.forEach((face, index) => drawFace(ctx, project, face, index === 2 ? "#6c7780" : "#8a5a46", "rgba(255,255,255,0.4)"));

    const dormerWall = [[-250, 4, 40], [-168, -28, 74], [-84, 18, 42], [-170, 54, 30]];
    drawFace(ctx, project, dormerWall, "#d8e1e8", "rgba(255,255,255,0.66)");
    const dormerRoof = screenPoints(project, [[-260, -2, 70], [-168, -72, 116], [-74, 8, 70], [-168, -28, 74]]);
    trace(ctx, dormerRoof);
    ctx.fillStyle = "#222c35";
    ctx.fill();
    ctx.strokeStyle = "rgba(255,255,255,0.7)";
    ctx.stroke();
    drawWindowUnit(ctx, project, [-168, 10, 48], 26, false);

    planes.forEach((plane) => {
      const label = project(plane.label);
      ctx.save();
      ctx.font = "900 13px Arial";
      const width = ctx.measureText(plane.name).width + 18;
      roundedRect(ctx, label[0] - 8, label[1] - 20, width, 26, 7);
      ctx.fillStyle = "rgba(7,24,39,0.82)";
      ctx.fill();
      ctx.strokeStyle = plane.accent;
      ctx.lineWidth = 2;
      ctx.stroke();
      ctx.fillStyle = "#ffffff";
      ctx.fillText(plane.name, label[0], label[1] - 3);
      ctx.restore();
    });
  }

  function drawBadge(ctx, x, y, text, color) {
    ctx.save();
    ctx.font = "900 12px Arial";
    const width = Math.max(54, ctx.measureText(text).width + 18);
    roundedRect(ctx, x, y, width, 28, 7);
    ctx.fillStyle = color;
    ctx.fill();
    ctx.strokeStyle = "rgba(255,255,255,0.88)";
    ctx.lineWidth = 1.5;
    ctx.stroke();
    ctx.fillStyle = "#ffffff";
    ctx.fillText(text, x + 9, y + 18);
    ctx.restore();
  }

  function drawDamagePin(ctx, project, point, label) {
    const base = project(point);
    const top = project([point[0], point[1], point[2] + 86]);
    ctx.save();
    ctx.strokeStyle = "rgba(237,27,36,0.74)";
    ctx.lineWidth = 2;
    ctx.beginPath();
    ctx.moveTo(base[0], base[1]);
    ctx.lineTo(top[0], top[1]);
    ctx.stroke();
    ctx.shadowColor = "rgba(237,27,36,0.66)";
    ctx.shadowBlur = 20;
    ctx.strokeStyle = "#ed1b24";
    ctx.lineWidth = 5;
    ctx.beginPath();
    ctx.ellipse(base[0], base[1], 28, 18, -0.2, 0, Math.PI * 2);
    ctx.stroke();
    ctx.shadowBlur = 0;
    ctx.fillStyle = "#ffffff";
    ctx.beginPath();
    ctx.arc(base[0], base[1], 5, 0, Math.PI * 2);
    ctx.fill();
    drawBadge(ctx, top[0] + 10, top[1] - 14, label, "#ed1b24");
    ctx.restore();
  }

  function drawRoute(ctx, project, route, time) {
    const points = route.map((point) => project(point));
    ctx.save();
    ctx.shadowColor = "rgba(237,27,36,0.5)";
    ctx.shadowBlur = 16;
    ctx.strokeStyle = "#ff3440";
    ctx.lineWidth = 5;
    ctx.setLineDash([12, 12]);
    ctx.beginPath();
    points.forEach((point, index) => {
      if (index === 0) ctx.moveTo(point[0], point[1]);
      else ctx.lineTo(point[0], point[1]);
    });
    ctx.stroke();
    ctx.setLineDash([]);
    ctx.shadowBlur = 0;

    points.forEach((point, index) => {
      ctx.fillStyle = index === 0 ? "#ffffff" : "#ed1b24";
      ctx.strokeStyle = "#6bb5e7";
      ctx.lineWidth = 3;
      ctx.beginPath();
      ctx.arc(point[0], point[1], 11, 0, Math.PI * 2);
      ctx.fill();
      ctx.stroke();
      ctx.font = "900 12px Arial";
      ctx.fillStyle = "#ffffff";
      ctx.fillText("WP-" + String(index + 1).padStart(2, "0"), point[0] + 15, point[1] - 10);
    });

    const t = ((time || Date.now()) / 3600) % 1;
    const segment = Math.min(points.length - 2, Math.floor(t * (points.length - 1)));
    const local = (t * (points.length - 1)) % 1;
    const a = points[segment];
    const b = points[segment + 1];
    const x = a[0] + (b[0] - a[0]) * local;
    const y = a[1] + (b[1] - a[1]) * local;
    drawDrone(ctx, x, y, Math.atan2(b[1] - a[1], b[0] - a[0]));
    ctx.restore();
  }

  function drawDrone(ctx, x, y, angle) {
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(angle);
    ctx.shadowColor = "rgba(0,0,0,0.36)";
    ctx.shadowBlur = 16;
    ctx.fillStyle = "#ffffff";
    ctx.strokeStyle = "#6bb5e7";
    ctx.lineWidth = 2.5;
    roundedRect(ctx, -22, -13, 44, 26, 7);
    ctx.fill();
    ctx.stroke();
    ctx.shadowBlur = 0;
    [[-46, -31], [46, -31], [-46, 31], [46, 31]].forEach((point) => {
      ctx.beginPath();
      ctx.arc(point[0], point[1], 12, 0, Math.PI * 2);
      ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(point[0] - 18, point[1]);
      ctx.lineTo(point[0] + 18, point[1]);
      ctx.moveTo(point[0], point[1] - 18);
      ctx.lineTo(point[0], point[1] + 18);
      ctx.stroke();
    });
    ctx.restore();
  }

  function drawCanvas(canvas, plan, time) {
    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    const size = fitCanvas(canvas);
    ctx.setTransform(size.ratio, 0, 0, size.ratio, 0, 0);
    drawBackground(ctx, size.width, size.height);
    const project = createProjector(size.width, size.height, time || Date.now());
    drawHouseShell(ctx, project);
    drawRoofModel(ctx, project);

    const route = [
      [-360, -250, 160],
      [-176, -208, 178],
      [26, -134, 190],
      [238, -190, 176],
      [326, 60, 154],
    ];

    drawRoute(ctx, project, route, time || Date.now());
    drawDamagePin(ctx, project, [-172, -92, 78], "D-01");
    drawDamagePin(ctx, project, [174, -74, 58], "D-02");
    drawDamagePin(ctx, project, [196, 94, 38], "D-03");

    const instructions = plan.flightInstructions || [];
    canvas.dataset.renderCheck = JSON.stringify({
      ok: true,
      style: "animated-3d-roof-model",
      waypoints: instructions.length || route.length,
      width: Math.round(size.width),
      height: Math.round(size.height),
    });
  }

  function renderInstructions(wrapper, plan) {
    const panel = wrapper.querySelector(".idr-instructions");
    if (!panel) return;

    const model = plan.model || {};
    const googleStatus = model.googleMapsConfigured ? "Google map context configured" : "Google key setting ready";
    const openAiStatus = model.openAiConfigured ? "AI model key configured" : "AI model key setting ready";
    const items = (plan.flightInstructions || []).map((item) => {
      return `<article><strong>${escapeHtml(item.id)} - ${escapeHtml(item.action)}</strong><span>${escapeHtml(item.altitudeFt)} ft / camera ${escapeHtml(item.cameraPitchDeg)} deg / plane ${escapeHtml(item.targetPlane)}</span><p>${escapeHtml(item.capture)}</p></article>`;
    });

    panel.innerHTML = `<article><strong>${escapeHtml(plan.missionId || "Inspector DroneProof Mission")}</strong><span>${escapeHtml(plan.generatedBy || "DroneProof Vision API")}</span><p>${escapeHtml(plan.fallbackReason || "Fallback mission ready.")}</p><p>${escapeHtml(googleStatus)} / ${escapeHtml(openAiStatus)}</p></article>${items.join("")}`;
  }

  function startRenderer(wrapper, plan) {
    const canvases = wrapper.querySelectorAll(".idr-canvas");
    if (!canvases.length) return;
    let lastDraw = 0;

    function frame(time) {
      if (!wrapper.isConnected) return;
      if (!document.hidden && (!lastDraw || time - lastDraw >= 120)) {
        canvases.forEach((canvas, index) => drawCanvas(canvas, plan, time + index * 420));
        lastDraw = time;
      }
      window.requestAnimationFrame(frame);
    }

    canvases.forEach((canvas, index) => drawCanvas(canvas, plan, Date.now() + index * 420));
    window.requestAnimationFrame(frame);
  }

  function downloadFile(filename, mime, content) {
    const blob = new Blob([content], { type: mime });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 800);
  }

  function formValue(form, name, fallback) {
    const field = form.elements[name];
    return field ? field.value : fallback;
  }

  function setFormValue(form, name, value) {
    const field = form.elements[name];
    if (!field || value === undefined || value === null || value === "") return;
    field.value = String(value);
    field.dispatchEvent(new Event("change", { bubbles: true }));
  }

  function roofDataSummary(data) {
    if (!data) return [];
    const rows = [];
    if (data.source) rows.push(["Source", data.source]);
    if (data.areaSqFt) rows.push(["Roof area", `${Math.round(Number(data.areaSqFt)).toLocaleString()} sq ft`]);
    if (data.squares) rows.push(["Squares", `${Number(data.squares).toFixed(2).replace(/\.00$/, "")}`]);
    if (data.pitchRise) rows.push(["Pitch", `${data.pitchRise}/${data.pitchRun || 12}`]);
    if (data.facets) rows.push(["Facets", data.facets]);
    if (data.confidence) rows.push(["Accuracy", `${data.confidence}/100`]);
    return rows;
  }

  function renderRoofBridge(wrapper, state, data, message) {
    const bridge = wrapper.querySelector("[data-idr-roof-bridge]");
    if (!bridge) return;
    const title = bridge.querySelector("[data-idr-roof-bridge-title]");
    const copy = bridge.querySelector("[data-idr-roof-bridge-copy]");
    const summary = bridge.querySelector("[data-idr-roof-data-summary]");
    bridge.classList.toggle("is-loaded", state === "loaded");
    bridge.classList.toggle("is-error", state === "error");
    bridge.classList.toggle("is-loading", state === "loading");
    if (title) {
      title.textContent = state === "loaded" ? "Roof data imported" : state === "error" ? "No roof data found" : state === "loading" ? "Importing roof data" : "Ready to import roof data";
    }
    if (copy) {
      copy.textContent = message || (state === "loaded" ? "Planner fields now use the latest saved roof-view measurement basis." : "Pull latest address, pitch, stories, roof size, and measurement basis from saved roof-view data.");
    }
    if (summary) {
      summary.innerHTML = state === "loaded"
        ? roofDataSummary(data).map(([key, value]) => `<div><dt>${escapeHtml(key)}</dt><dd>${escapeHtml(value)}</dd></div>`).join("")
        : "";
    }
  }

  function applyRoofData(wrapper, form, data) {
    wrapper.idrRoofData = data || null;
    if (!data) return;
    const job = data.jobId ? String(data.jobId) : "";
    setFormValue(form, "jobId", job && /^(IRV|IR|WP)-/i.test(job) ? job : job ? `IRV-${job}` : "");
    setFormValue(form, "address", data.address);
    setFormValue(form, "roofStyle", data.roofStyle);
    setFormValue(form, "stories", data.stories);
    setFormValue(form, "pitch", data.pitch);
  }

  function numberFromText(text, pattern) {
    const match = String(text || "").match(pattern);
    return match ? Number(String(match[1]).replace(/,/g, "")) || 0 : 0;
  }

  function parseRoofReportText(text) {
    const value = String(text || "").trim();
    const addressMatch = value.match(/\b\d{2,6}\s+[A-Za-z0-9 .#-]+,\s*[A-Za-z .-]+,\s*[A-Z]{2}(?:\s+\d{5})?\b/);
    const pitchMatch = value.match(/([0-9]+(?:\.[0-9]+)?)\s*\/\s*12/);
    const storiesMatch = value.match(/\b([123])\s*(?:story|stories)\b/i);
    const styleMatch = value.match(/\b(hip|gable|complex|flat)\b/i);
    return {
      source: "Pasted InstantRoofView / roof report text",
      address: addressMatch ? addressMatch[0] : "",
      areaSqFt: numberFromText(value, /(?:roof\s*area|surface\s*area|total\s*area|area)[^0-9]{0,30}([0-9,]+(?:\.[0-9]+)?)/i),
      squares: numberFromText(value, /(?:squares|order\s*squares|final\s*squares)[^0-9]{0,30}([0-9,]+(?:\.[0-9]+)?)/i),
      facets: numberFromText(value, /(?:facets|planes|segments)[^0-9]{0,30}([0-9,]+(?:\.[0-9]+)?)/i),
      stories: storiesMatch ? Number(storiesMatch[1]) : 2,
      pitchRise: pitchMatch ? Number(pitchMatch[1]) : 7,
      pitchRun: 12,
      roofStyle: styleMatch ? styleMatch[1].toLowerCase() : "hip",
      ridges: numberFromText(value, /(?:ridges?|ridge\s*lf)[^0-9]{0,30}([0-9,]+(?:\.[0-9]+)?)/i),
      hips: numberFromText(value, /(?:hips?|hip\s*lf)[^0-9]{0,30}([0-9,]+(?:\.[0-9]+)?)/i),
      valleys: numberFromText(value, /(?:valleys?|valley\s*lf)[^0-9]{0,30}([0-9,]+(?:\.[0-9]+)?)/i),
      eaves: numberFromText(value, /(?:eaves?|eave\s*lf|starter)[^0-9]{0,30}([0-9,]+(?:\.[0-9]+)?)/i),
      rakes: numberFromText(value, /(?:rakes?|rake\s*lf)[^0-9]{0,30}([0-9,]+(?:\.[0-9]+)?)/i),
      confidence: 92,
    };
  }

  function readPastedRoofData(text) {
    const value = String(text || "").trim();
    if (!value) throw new Error("Paste InstantRoofView JSON or roof report text first.");
    try {
      const parsed = JSON.parse(value);
      return parsed && parsed.data && typeof parsed.data === "object" ? parsed.data : parsed;
    } catch (error) {
      return parseRoofReportText(value);
    }
  }

  async function savePastedRoofData(wrapper, form) {
    const config = readConfig(wrapper);
    const field = wrapper.querySelector("[data-idr-roof-json]");
    if (!config.roofDataSaveApi) {
      renderRoofBridge(wrapper, "error", null, "Roof data save route is not available in this install.");
      return null;
    }

    let data;
    try {
      data = readPastedRoofData(field ? field.value : "");
    } catch (error) {
      renderRoofBridge(wrapper, "error", null, error.message || "Paste roof data first.");
      return null;
    }

    renderRoofBridge(wrapper, "loading", null, "Saving pasted roof data.");

    try {
      const response = await fetch(config.roofDataSaveApi, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          ...(config.restNonce ? { "X-WP-Nonce": config.restNonce } : {}),
        },
        body: JSON.stringify({ data }),
      });
      const payload = await response.json();
      if (!response.ok || !payload.ok || !payload.data) {
        throw new Error(payload.message || "Roof data could not be saved.");
      }
      applyRoofData(wrapper, form, payload.data);
      renderRoofBridge(wrapper, "loaded", payload.data, payload.message || "Roof data saved.");
      return payload.data;
    } catch (error) {
      renderRoofBridge(wrapper, "error", null, error.message || "Roof data could not be saved.");
      return null;
    }
  }

  async function importRoofData(wrapper, form) {
    const config = readConfig(wrapper);
    if (!config.roofDataApi) {
      renderRoofBridge(wrapper, "error", null, "Roof data bridge is not available in this install.");
      return null;
    }

    renderRoofBridge(wrapper, "loading", null, "Checking saved InstantRoofView and roof-app data.");

    try {
      const response = await fetch(config.roofDataApi, {
        credentials: "same-origin",
        headers: config.restNonce ? { "X-WP-Nonce": config.restNonce } : {},
      });
      const payload = await response.json();
      if (!response.ok || !payload.ok || !payload.data) {
        throw new Error(payload.message || "No saved roof data found.");
      }

      applyRoofData(wrapper, form, payload.data);
      renderRoofBridge(wrapper, "loaded", payload.data, payload.message || "Roof data imported.");
      return payload.data;
    } catch (error) {
      renderRoofBridge(wrapper, "error", null, error.message || "Could not import roof data.");
      return null;
    }
  }

  function buildMissionFromForm(form, geo) {
    const wrapper = form.closest(".idr-wrap");
    const roofData = wrapper && wrapper.idrRoofData ? wrapper.idrRoofData : null;
    const jobId = formValue(form, "jobId", "IR-CLAIM-001") || "IR-CLAIM-001";
    const address = formValue(form, "address", "");
    const roofStyle = formValue(form, "roofStyle", "hip");
    const stories = Number(formValue(form, "stories", "2"));
    const pitch = formValue(form, "pitch", "standard");
    const drone = formValue(form, "drone", "DJI Mavic 3 Enterprise");
    const mission = formValue(form, "mission", "claim");
    const overlap = Number(formValue(form, "overlap", "40"));
    const pitchBoost = { low: 0, standard: 8, steep: 16, extreme: 24 }[pitch] || 8;
    const baseAltitude = 38 + stories * 12 + pitchBoost;
    const gridAltitude = Math.max(42, baseAltitude - 18);
    const detailAltitude = Math.max(34, gridAltitude - 10);
    const styleCount = { gable: 5, hip: 6, complex: 8, flat: 7 }[roofStyle] || 6;
    const planes = ["A", "B", "C", "D", "Ridge", "Valley", "Perimeter", "Collateral"];
    const actions = [
      "Four-corner overview",
      "Front slope grid",
      "Rear slope grid",
      "Right return sweep",
      "Left return sweep",
      "Ridge and penetrations",
      "Valley and flashing pass",
      "Gutter and collateral pass",
    ];

    const flightInstructions = actions.slice(0, styleCount).map((action, index) => {
      const isOverview = index === 0;
      const isDetail = index > 4;
      return {
        id: "WP-" + String(index + 1).padStart(2, "0"),
        action,
        altitudeFt: isOverview ? baseAltitude + 24 : isDetail ? detailAltitude : gridAltitude,
        cameraPitchDeg: isOverview ? -58 : isDetail ? -48 : -72,
        headingDeg: (index * 47 + 35) % 360,
        targetPlane: planes[index] || "A",
        capture: isOverview
          ? "Property context, all roof faces, access, and street reference."
          : action + " with " + overlap + " percent overlap for " + mission + " documentation.",
      };
    });

    return {
      missionId: jobId,
      generatedBy: "Inspector DroneProof Contractor Planner",
      fallbackReason: geo && geo.ok ? "GPS center locked from saved Google key." : "Relative waypoint mode. Verify position in DJI before flight.",
      address,
      drone,
      roofStyle,
      stories,
      pitch,
      overlap,
      roofData,
      geo: geo || { ok: false, mode: "relative" },
      flightInstructions,
      exports: ["dji kml", "litchi csv", "csv waypoints", "contractor packet json", "photo manifest", "field app", "print report"],
    };
  }

  function missionCsv(mission) {
    const rows = [["id", "action", "altitudeFt", "cameraPitchDeg", "headingDeg", "targetPlane", "capture"]];
    (mission.flightInstructions || []).forEach((item) => {
      rows.push([item.id, item.action, item.altitudeFt, item.cameraPitchDeg, item.headingDeg, item.targetPlane, item.capture]);
    });
    return rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(",")).join("\n");
  }

  function safeJson(value) {
    return JSON.stringify(value, null, 2).replace(/</g, "\\u003c");
  }

  function collectPreflight(wrapper) {
    const inputs = Array.from(wrapper.querySelectorAll("[data-idr-preflight] input[type='checkbox']"));
    const items = inputs.map((input) => ({
      key: input.value,
      label: preflightLabels[input.value] || input.parentElement.textContent.trim(),
      checked: input.checked,
    }));
    const complete = items.filter((item) => item.checked).length;
    return {
      complete,
      total: items.length,
      ready: items.length > 0 && complete === items.length,
      items,
    };
  }

  function updatePreflightGate(wrapper) {
    const gate = wrapper.querySelector("[data-idr-preflight]");
    if (!gate) return;
    const preflight = collectPreflight(wrapper);
    const title = gate.querySelector("[data-idr-preflight-title]");
    const status = gate.querySelector("[data-idr-preflight-status]");
    gate.classList.toggle("is-ready", preflight.ready);
    if (title) title.textContent = `${preflight.complete}/${preflight.total} ready`;
    if (status) status.textContent = preflight.ready ? "Pilot gate ready for final app review" : "Hold export for pilot review";
  }

  function setupPreflightGate(wrapper) {
    const gate = wrapper.querySelector("[data-idr-preflight]");
    if (!gate) return;
    gate.querySelectorAll("input[type='checkbox']").forEach((input) => {
      input.addEventListener("change", () => updatePreflightGate(wrapper));
    });
    updatePreflightGate(wrapper);
  }

  function preflightText(mission, wrapper) {
    const preflight = collectPreflight(wrapper);
    const lines = [
      "Inspector DroneProof Pilot Preflight",
      "Mission: " + (mission.missionId || "DroneProof Mission"),
      "Address: " + (mission.address || "Relative plan"),
      "Drone: " + (mission.drone || "DJI aircraft"),
      "GPS: " + (mission.geo && mission.geo.ok ? "Locked" : "Relative / review required"),
      "Readiness: " + preflight.complete + "/" + preflight.total,
      "",
      "Pilot launch gate:",
    ];
    preflight.items.forEach((item) => {
      lines.push((item.checked ? "[x] " : "[ ] ") + item.label);
    });
    lines.push("", "Planning aid only. Verify final route inside the approved flight app before launch.");
    return lines.join("\n");
  }

  function missionCoordinates(mission) {
    const geo = mission.geo || {};
    const hasGps = Boolean(geo.ok && Number.isFinite(Number(geo.lat)) && Number.isFinite(Number(geo.lng)));
    const centerLat = hasGps ? Number(geo.lat) : 34.0754;
    const centerLng = hasGps ? Number(geo.lng) : -84.2941;
    const feetPerDegreeLat = 364000;
    const feetPerDegreeLng = Math.max(1, 364000 * Math.cos(centerLat * Math.PI / 180));
    const points = (mission.flightInstructions || []).map((item, index) => {
      const heading = Number(item.headingDeg || index * 45);
      const radians = heading * Math.PI / 180;
      const distanceFt = 48 + index * 28 + (index % 2 ? 18 : 0);
      const lat = centerLat + Math.cos(radians) * distanceFt / feetPerDegreeLat;
      const lng = centerLng + Math.sin(radians) * distanceFt / feetPerDegreeLng;
      return {
        ...item,
        lat: Math.round(lat * 10000000) / 10000000,
        lng: Math.round(lng * 10000000) / 10000000,
        altitudeM: Math.round(Number(item.altitudeFt || 50) * 0.3048 * 10) / 10,
        reviewOnly: !hasGps,
      };
    });

    return { hasGps, centerLat, centerLng, points };
  }

  function missionKml(mission) {
    const coords = missionCoordinates(mission);
    const warning = coords.hasGps
      ? "GPS center came from the saved Google geocode route. Pilot must still verify every waypoint in the drone app before launch."
      : "REVIEW ONLY: no GPS lock. Enter the property address, generate again, and verify coordinates before importing into a drone app.";
    const line = coords.points.map((point) => `${point.lng},${point.lat},${point.altitudeM}`).join(" ");
    const placemarks = coords.points.map((point) => {
      return `<Placemark><name>${escapeHtml(point.id || "WP")}</name><description>${escapeHtml(point.action || "")} | Alt ${escapeHtml(point.altitudeFt || "")} ft | Camera ${escapeHtml(point.cameraPitchDeg || "")} deg | ${escapeHtml(warning)}</description><Point><altitudeMode>relativeToGround</altitudeMode><coordinates>${point.lng},${point.lat},${point.altitudeM}</coordinates></Point></Placemark>`;
    }).join("");

    return `<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <name>${escapeHtml(mission.missionId || "Inspector DroneProof Mission")}</name>
    <description>${escapeHtml(warning)}</description>
    <Style id="flightPath"><LineStyle><color>ff241bed</color><width>4</width></LineStyle></Style>
    ${placemarks}
    <Placemark>
      <name>DroneProof capture path</name>
      <styleUrl>#flightPath</styleUrl>
      <LineString><altitudeMode>relativeToGround</altitudeMode><coordinates>${line}</coordinates></LineString>
    </Placemark>
  </Document>
</kml>`;
  }

  function litchiCsv(mission) {
    const coords = missionCoordinates(mission);
    const rows = [[
      "latitude",
      "longitude",
      "altitude(m)",
      "heading",
      "curvesize(m)",
      "rotationdir",
      "gimbalmode",
      "gimbalpitch",
      "actiontype1",
      "actionparam1",
      "notes",
    ]];

    coords.points.forEach((point) => {
      rows.push([
        point.lat,
        point.lng,
        point.altitudeM,
        point.headingDeg || 0,
        0,
        0,
        2,
        point.cameraPitchDeg || -70,
        1,
        0,
        point.reviewOnly ? "REVIEW ONLY - GPS not locked" : point.action,
      ]);
    });

    return rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(",")).join("\n");
  }

  function localQa(mission, markers, preflight) {
    const waypoints = mission.flightInstructions || [];
    const gate = preflight || { ready: false, complete: 0, total: 0, items: [] };
    const issues = [];
    const checks = [
      "Confirm FAA/Part 107 status, airspace, TFRs, weather, battery, GPS lock, compass, VLOS, and return-to-home altitude.",
      "Review tree, wire, chimney, vehicle, person, and neighboring-property clearance before importing or flying.",
      "Use the exported DJI/Litchi files as planning files only until the pilot verifies every waypoint in the aircraft app.",
    ];

    if (!mission.geo || !mission.geo.ok) {
      issues.push("GPS is not locked. Enter the property address and generate the plan before using DJI KML or Litchi CSV in the field.");
    }

    if (waypoints.length < 5) {
      issues.push("Add at least five capture points: overview, front, rear, returns, ridge/penetrations, and collateral pass.");
    }

    if (!markers.length) {
      issues.push("No photo damage markers are attached yet. Add markers before exporting a supplement or denial-response packet.");
    }

    if (gate.total && !gate.ready) {
      issues.push(`Pilot launch gate is ${gate.complete}/${gate.total}. Complete every preflight item before field launch.`);
    }

    waypoints.forEach((waypoint) => {
      if (Number(waypoint.altitudeFt || 0) > 120) {
        issues.push(`${waypoint.id || "A waypoint"} is above 120 ft. Verify legal ceiling, terrain, and waiver status.`);
      }
    });

    return {
      ok: true,
      mode: "local-rules",
      summary: issues.length ? "QA found items to verify before flight/export." : "QA looks ready for pilot review and packet export.",
      checks,
      issues: issues.length ? issues : ["Plan structure, waypoint count, and photo marker set look ready for final pilot review."],
    };
  }

  function renderQa(wrapper, result) {
    const panel = wrapper.querySelector("[data-idr-qa-panel]");
    if (!panel) return;
    panel.classList.remove("is-running");
    panel.classList.add("is-complete");

    const title = panel.querySelector("strong");
    const detail = panel.querySelector("small");
    if (title) title.textContent = result.mode === "openai" ? "AI QA complete" : "Local QA complete";
    if (detail) detail.textContent = result.summary || "QA finished.";

    let output = panel.querySelector("[data-idr-qa-results]");
    if (!output) {
      output = document.createElement("div");
      output.className = "idr-qa-results";
      output.setAttribute("data-idr-qa-results", "");
      panel.appendChild(output);
    }

    const checks = (result.checks || []).map((item) => `<li>${escapeHtml(item)}</li>`).join("");
    const issues = (result.issues || []).map((item) => `<li>${escapeHtml(item)}</li>`).join("");
    output.innerHTML = `<p>${escapeHtml(result.summary || "QA finished.")}</p>${checks ? `<strong>Required pilot checks</strong><ul>${checks}</ul>` : ""}${issues ? `<strong>Items to verify</strong><ul>${issues}</ul>` : ""}`;
  }

  async function runQa(wrapper) {
    const config = readConfig(wrapper);
    const panel = wrapper.querySelector("[data-idr-qa-panel]");
    const mission = wrapper.idrMission || {};
    const markers = (wrapper.idrPhotos && wrapper.idrPhotos.markers) || [];
    const preflight = collectPreflight(wrapper);

    if (panel) {
      panel.classList.add("is-running");
      const title = panel.querySelector("strong");
      const detail = panel.querySelector("small");
      if (title) title.textContent = "Reviewing plan...";
      if (detail) detail.textContent = "Checking waypoints, safety reminders, photo markers, and packet readiness.";
    }

    if (!config.aiQaApi) {
      renderQa(wrapper, localQa(mission, markers, preflight));
      return;
    }

    try {
      const response = await fetch(config.aiQaApi, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": config.restNonce || "",
        },
        body: JSON.stringify({ mission, markers, preflight }),
      });
      if (!response.ok) throw new Error("QA route failed");
      renderQa(wrapper, await response.json());
    } catch (error) {
      renderQa(wrapper, localQa(mission, markers, preflight));
    }
  }

  function fieldAppHtml(mission, photos, preflight) {
    const markers = photos.markers || [];
    const photoManifest = (photos.photos || []).map((photo) => ({
      id: photo.id,
      name: photo.name,
      size: photo.size,
    }));
    const payload = {
      generatedAt: new Date().toISOString(),
      mission,
      photoManifest,
      markers,
      preflight,
      pilotNotice: "Planning aid only. Verify FAA/airspace/obstacles/battery/RTH/VLOS and approve final path in the drone app before flight.",
    };
    const rows = (mission.flightInstructions || []).map((item) => `<tr><td>${escapeHtml(item.id)}</td><td>${escapeHtml(item.action)}</td><td>${escapeHtml(item.altitudeFt)} ft</td><td>${escapeHtml(item.cameraPitchDeg)} deg</td><td>${escapeHtml(item.targetPlane)}</td></tr>`).join("");
    const markerRows = markers.map((marker) => `<tr><td>${escapeHtml(marker.id)}</td><td>${escapeHtml(marker.photoName)}</td><td>${escapeHtml(marker.plane)}</td><td>${escapeHtml(marker.type)}</td><td>${escapeHtml(marker.severity)}</td><td>${escapeHtml(marker.note || "")}</td></tr>`).join("");
    const preflightRows = (preflight.items || []).map((item) => `<tr><td>${item.checked ? "Ready" : "Open"}</td><td>${escapeHtml(item.label)}</td></tr>`).join("");

    return `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>DroneProof Field App</title><style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,sans-serif;margin:0;color:#071827;background:#f5f8fb}header{background:#071827;color:#fff;padding:24px}main{padding:20px;display:grid;gap:18px}.card{background:#fff;border:1px solid #d7e3ee;border-radius:8px;padding:18px;box-shadow:0 14px 44px rgba(7,24,39,.08)}h1,h2{margin:0 0 10px}h1{font-size:28px}h2{font-size:20px;color:#0f75bc}p{line-height:1.5}table{border-collapse:collapse;width:100%;font-size:13px}td,th{border-bottom:1px solid #d7e3ee;padding:9px;text-align:left}th{color:#526377;text-transform:uppercase}.warn{border-left:5px solid #ed1b24}.pill{background:#ed1b24;border-radius:999px;color:#fff;display:inline-block;font-weight:800;padding:6px 10px}</style></head><body><header><span class="pill">DroneProof Vision</span><h1>${escapeHtml(mission.missionId || "Field mission")}</h1><p>${escapeHtml(mission.address || "Relative plan")}</p></header><main><section class="card warn"><h2>Pilot notice</h2><p>${escapeHtml(payload.pilotNotice)}</p></section><section class="card"><h2>Pilot launch gate</h2><p>${escapeHtml(preflight.complete + "/" + preflight.total)} ready</p><table><thead><tr><th>Status</th><th>Check</th></tr></thead><tbody>${preflightRows}</tbody></table></section><section class="card"><h2>Waypoints</h2><table><thead><tr><th>ID</th><th>Action</th><th>Alt</th><th>Camera</th><th>Plane</th></tr></thead><tbody>${rows}</tbody></table></section><section class="card"><h2>Damage markers</h2><table><thead><tr><th>ID</th><th>Photo</th><th>Plane</th><th>Damage</th><th>Severity</th><th>Note</th></tr></thead><tbody>${markerRows || "<tr><td colspan='6'>No markers yet</td></tr>"}</tbody></table></section><section class="card"><h2>Raw packet JSON</h2><pre>${escapeHtml(safeJson(payload))}</pre></section></main><script type="application/json" id="droneproof-packet">${safeJson(payload)}</script></body></html>`;
  }

  function updatePlanUi(wrapper, mission) {
    const status = wrapper.querySelector("[data-idr-plan-status]");
    const metrics = wrapper.querySelector("[data-idr-plan-metrics]");
    const table = wrapper.querySelector("[data-idr-waypoints]");
    const waypoints = mission.flightInstructions || [];
    const mode = mission.geo && mission.geo.ok ? "GPS locked" : "Relative flight";
    const geoLabel = mission.geo && (mission.geo.formattedAddress || mission.geo.address || mission.address || mission.geo.source || "Verified coordinates");
    const detail = mission.geo && mission.geo.ok
      ? geoLabel + " / " + mission.geo.lat.toFixed(6) + ", " + mission.geo.lng.toFixed(6)
      : "Use DJI map/manual placement before launch.";

    if (status) {
      status.innerHTML = `<span>Mode</span><strong>${escapeHtml(mode)}</strong><small>${escapeHtml(detail)}</small>`;
    }

    if (metrics) {
      const grid = waypoints[1] ? waypoints[1].altitudeFt : 54;
      metrics.innerHTML = `<span><strong>${escapeHtml(waypoints.length)}</strong> waypoints</span><span><strong>${escapeHtml(grid)} ft</strong> grid pass</span><span><strong>${escapeHtml(mission.overlap)}%</strong> overlap</span>`;
    }

    if (table) {
      table.innerHTML = `<table><thead><tr><th>ID</th><th>Action</th><th>Alt</th><th>Pitch</th><th>Plane</th></tr></thead><tbody>${waypoints.map((item) => `<tr><td>${escapeHtml(item.id)}</td><td>${escapeHtml(item.action)}</td><td>${escapeHtml(item.altitudeFt)} ft</td><td>${escapeHtml(item.cameraPitchDeg)} deg</td><td>${escapeHtml(item.targetPlane)}</td></tr>`).join("")}</tbody></table>`;
    }
  }

  async function geocodeAddress(wrapper, address) {
    const config = readConfig(wrapper);
    if (!address || !config.geocodeApi) return null;

    try {
      const response = await fetch(config.geocodeApi, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": config.restNonce || "",
        },
        body: JSON.stringify({ address }),
      });
      if (!response.ok) return null;
      return response.json();
    } catch (error) {
      return null;
    }
  }

  function setupPlanner(wrapper, initialPlan) {
    const form = wrapper.querySelector("[data-idr-planner]");
    if (!form) return;
    setupPreflightGate(wrapper);

    function setMission(mission) {
      wrapper.idrMission = mission;
      updatePlanUi(wrapper, mission);
      renderInstructions(wrapper, mission);
    }

    setMission(buildMissionFromForm(form, null));
    renderRoofBridge(wrapper, "ready", null);

    const roofImportButton = wrapper.querySelector("[data-idr-roof-import]");
    if (roofImportButton) {
      roofImportButton.addEventListener("click", async () => {
        roofImportButton.disabled = true;
        const data = await importRoofData(wrapper, form);
        if (data) {
          setMission(buildMissionFromForm(form, null));
        }
        roofImportButton.disabled = false;
      });
    }

    const roofSaveButton = wrapper.querySelector("[data-idr-roof-save]");
    if (roofSaveButton) {
      roofSaveButton.addEventListener("click", async () => {
        roofSaveButton.disabled = true;
        const data = await savePastedRoofData(wrapper, form);
        if (data) {
          setMission(buildMissionFromForm(form, null));
        }
        roofSaveButton.disabled = false;
      });
    }

    form.addEventListener("submit", async (event) => {
      event.preventDefault();
      const address = formValue(form, "address", "");
      const geo = await geocodeAddress(wrapper, address);
      setMission(buildMissionFromForm(form, geo));
    });

    async function missionForExport(type) {
      const current = wrapper.idrMission || initialPlan;
      if ((type !== "dji" && type !== "litchi") || (current.geo && current.geo.ok)) {
        return current;
      }

      const address = formValue(form, "address", "");
      const geo = await geocodeAddress(wrapper, address);
      const next = buildMissionFromForm(form, geo);
      setMission(next);

      if (!next.geo || !next.geo.ok) {
        window.alert("No GPS lock yet. The exported flight file is marked REVIEW ONLY. Enter the property address, regenerate, and verify coordinates in the drone app before any flight.");
      }

      return next;
    }

    wrapper.querySelectorAll("[data-idr-export]").forEach((button) => {
      button.addEventListener("click", async () => {
        const type = button.getAttribute("data-idr-export");
        const mission = await missionForExport(type);
        const photos = wrapper.idrPhotos || { photos: [], markers: [] };
        const preflight = collectPreflight(wrapper);
        if (type === "dji") downloadFile("droneproof-dji-mission.kml", "application/vnd.google-earth.kml+xml", missionKml(mission));
        if (type === "litchi") downloadFile("droneproof-litchi-waypoints.csv", "text/csv", litchiCsv(mission));
        if (type === "csv") downloadFile("droneproof-waypoints.csv", "text/csv", missionCsv(mission));
        if (type === "json") downloadFile("droneproof-mission.json", "application/json", JSON.stringify(mission, null, 2));
        if (type === "preflight") downloadFile("droneproof-preflight-checklist.txt", "text/plain", preflightText(mission, wrapper));
        if (type === "app") downloadFile("droneproof-field-app.html", "text/html", fieldAppHtml(mission, photos, preflight));
        if (type === "packet") {
          downloadFile("droneproof-contractor-packet.json", "application/json", JSON.stringify({
            mission,
            preflight,
            photos: photos.photos.map((photo) => ({ id: photo.id, name: photo.name, size: photo.size })),
            markers: photos.markers,
          }, null, 2));
        }
      });
    });

    const qaButton = wrapper.querySelector("[data-idr-ai-qa]");
    if (qaButton) {
      qaButton.addEventListener("click", () => runQa(wrapper));
    }
  }

  function setupPhotoLab(wrapper) {
    const input = wrapper.querySelector(".idr-photo-input");
    const thumbs = wrapper.querySelector("[data-idr-photo-thumbs]");
    const stage = wrapper.querySelector("[data-idr-photo-stage]");
    const list = wrapper.querySelector("[data-idr-damage-list]");
    if (!input || !thumbs || !stage || !list) return;

    const config = readConfig(wrapper);
    const state = { photos: [], active: -1, markers: [] };
    wrapper.idrPhotos = state;

    function activePhoto() {
      return state.photos[state.active] || null;
    }

    function markerControls() {
      return {
        plane: (wrapper.querySelector("[data-idr-marker-plane]") || {}).value || "A",
        type: (wrapper.querySelector("[data-idr-marker-type]") || {}).value || "Damage",
        severity: (wrapper.querySelector("[data-idr-marker-severity]") || {}).value || "Medium",
        note: (wrapper.querySelector("[data-idr-marker-note]") || {}).value || "",
      };
    }

    function renderThumbs() {
      thumbs.innerHTML = state.photos.map((photo, index) => `<button type="button" class="${index === state.active ? "is-active" : ""}" data-photo-index="${index}"><img src="${photo.url}" alt=""><span>${escapeHtml(photo.name)}</span></button>`).join("");
      thumbs.querySelectorAll("[data-photo-index]").forEach((button) => {
        button.addEventListener("click", () => {
          state.active = Number(button.getAttribute("data-photo-index"));
          renderAll();
        });
      });
    }

    function renderStage() {
      const photo = activePhoto();
      if (!photo) {
        stage.innerHTML = '<div class="idr-photo-empty">Choose a photo, then click the image to place damage markers.</div>';
        return;
      }
      const markers = state.markers.filter((marker) => marker.photoId === photo.id);
      stage.innerHTML = `<img src="${photo.url}" alt="${escapeHtml(photo.name)}">${markers.map((marker) => `<button type="button" class="idr-marker-dot severity-${escapeHtml(marker.severity.toLowerCase())}" style="left:${marker.x}%;top:${marker.y}%;" title="${escapeHtml(marker.type)}">${escapeHtml(marker.id)}</button>`).join("")}`;
    }

    function renderList() {
      if (!state.markers.length) {
        list.innerHTML = '<p>No damage markers yet.</p>';
        return;
      }
      list.innerHTML = state.markers.map((marker) => `<article><strong>${escapeHtml(marker.id)} / Plane ${escapeHtml(marker.plane)}</strong><span>${escapeHtml(marker.type)} - ${escapeHtml(marker.severity)}</span><p>${escapeHtml(marker.note || "No note")}</p></article>`).join("");
    }

    function renderAll() {
      renderThumbs();
      renderStage();
      renderList();
    }

    function syncPanel(stateName, title, copy, reportUrl) {
      const panel = wrapper.querySelector("[data-idr-field-sync]");
      if (!panel) return;
      panel.classList.toggle("is-loading", stateName === "loading");
      panel.classList.toggle("is-ready", stateName === "ready");
      panel.classList.toggle("is-error", stateName === "error");
      const titleEl = panel.querySelector("[data-idr-field-sync-title]");
      const copyEl = panel.querySelector("[data-idr-field-sync-copy]");
      const link = panel.querySelector("[data-idr-field-report-link]");
      if (titleEl) titleEl.textContent = title;
      if (copyEl) copyEl.textContent = copy;
      if (link && reportUrl) link.href = reportUrl;
    }

    function photoManifest() {
      return state.photos.map((photo) => ({
        id: photo.id,
        name: photo.name,
        size: photo.size,
        url: photo.remoteUrl || "",
        plane: "",
        damage: "",
        severity: "",
        note: "",
      }));
    }

    async function saveFieldJob() {
      const mission = wrapper.idrMission || {};
      const preflight = collectPreflight(wrapper);
      const jobId = mission.missionId || "IR-CLAIM-001";

      if (!config.fieldJobApi) {
        syncPanel("error", "Field sync unavailable", "This install does not expose the field job route yet.");
        return null;
      }

      syncPanel("loading", "Saving job", "Sending mission, preflight, photo manifest, and damage markers to WordPress.");

      try {
        const response = await fetch(config.fieldJobApi, {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
            ...(config.restNonce ? { "X-WP-Nonce": config.restNonce } : {}),
          },
          body: JSON.stringify({
            source: "DroneProof web console",
            jobId,
            mission,
            preflight,
            photos: photoManifest(),
            markers: state.markers,
          }),
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok) {
          throw new Error(payload.message || "WordPress did not save the field job.");
        }
        wrapper.idrFieldJob = payload.job;
        syncPanel("ready", "Job saved to WordPress", `${payload.photoCount || 0} photos and ${payload.markerCount || 0} markers are tied to ${payload.jobId}.`, payload.reportUrl);
        return payload;
      } catch (error) {
        syncPanel("error", "Job sync blocked", error.message || "Log in or use the Android field token to sync this job.");
        return null;
      }
    }

    async function uploadFieldPhotos() {
      const mission = wrapper.idrMission || {};
      const jobId = mission.missionId || "IR-CLAIM-001";
      const uploadable = state.photos.filter((photo) => photo.file && !photo.remoteUrl);

      if (!config.fieldPhotoApi) {
        syncPanel("error", "Photo upload unavailable", "This install does not expose the field photo upload route yet.");
        return;
      }

      if (!uploadable.length) {
        syncPanel("ready", "No new files to upload", "Sample images and already-uploaded photos stay in the local proof view.");
        return;
      }

      syncPanel("loading", "Uploading field photos", `Uploading ${uploadable.length} roof photo${uploadable.length === 1 ? "" : "s"} to WordPress media.`);

      let uploaded = 0;
      for (const photo of uploadable) {
        const marker = state.markers.find((item) => item.photoId === photo.id) || {};
        const form = new FormData();
        form.append("jobId", jobId);
        form.append("photoId", photo.id);
        form.append("plane", marker.plane || "");
        form.append("damage", marker.type || "");
        form.append("severity", marker.severity || "");
        form.append("note", marker.note || "");
        form.append("photo", photo.file, photo.name);

        const response = await fetch(config.fieldPhotoApi, {
          method: "POST",
          credentials: "same-origin",
          headers: config.restNonce ? { "X-WP-Nonce": config.restNonce } : {},
          body: form,
        });
        const payload = await response.json();
        if (!response.ok || !payload.ok || !payload.photo) {
          throw new Error(payload.message || `Could not upload ${photo.name}.`);
        }
        uploaded += 1;
        photo.remoteUrl = payload.photo.url;
        photo.attachmentId = payload.photo.attachmentId;
        syncPanel("loading", "Uploading field photos", `${uploaded}/${uploadable.length} uploaded. ${photo.name} is stored in WordPress media.`);
      }

      const saved = await saveFieldJob();
      syncPanel("ready", "Photos uploaded", `${uploaded} real roof photo${uploaded === 1 ? "" : "s"} stored and tied to the job.`, saved && saved.reportUrl);
      renderAll();
    }

    input.addEventListener("change", () => {
      Array.from(input.files || []).forEach((file) => {
        state.photos.push({
          id: "P-" + String(state.photos.length + 1).padStart(3, "0"),
          name: file.name,
          size: file.size,
          url: URL.createObjectURL(file),
          file,
        });
      });
      if (state.active === -1 && state.photos.length) state.active = 0;
      renderAll();
    });

    stage.addEventListener("click", (event) => {
      const photo = activePhoto();
      if (!photo || event.target.classList.contains("idr-marker-dot")) return;
      const rect = stage.getBoundingClientRect();
      const controls = markerControls();
      const x = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100));
      const y = Math.max(0, Math.min(100, ((event.clientY - rect.top) / rect.height) * 100));
      state.markers.push({
        id: "D-" + String(state.markers.length + 1).padStart(2, "0"),
        photoId: photo.id,
        photoName: photo.name,
        x: Math.round(x * 10) / 10,
        y: Math.round(y * 10) / 10,
        plane: controls.plane,
        type: controls.type,
        severity: controls.severity,
        note: controls.note,
      });
      renderAll();
    });

    const printButton = wrapper.querySelector("[data-idr-print-report]");
    if (printButton) {
      printButton.addEventListener("click", () => {
        const mission = wrapper.idrMission || {};
        const report = window.open("", "_blank");
        if (!report) return;
        const markerRows = state.markers.map((marker) => `<tr><td>${escapeHtml(marker.id)}</td><td>${escapeHtml(marker.photoName)}</td><td>${escapeHtml(marker.plane)}</td><td>${escapeHtml(marker.type)}</td><td>${escapeHtml(marker.severity)}</td><td>${escapeHtml(marker.note || "")}</td></tr>`).join("");
        report.document.write(`<html><head><title>DroneProof Contractor Report</title><style>body{font-family:Arial,sans-serif;color:#071827;padding:28px}h1{color:#0f75bc}table{border-collapse:collapse;width:100%}td,th{border:1px solid #c9d8e7;padding:8px;text-align:left}th{background:#f4f8fc}</style></head><body><h1>DroneProof Contractor Report</h1><p><strong>Mission:</strong> ${escapeHtml(mission.missionId || "Mission")}</p><p><strong>Address:</strong> ${escapeHtml(mission.address || "Relative plan")}</p><h2>Damage Markers</h2><table><thead><tr><th>ID</th><th>Photo</th><th>Plane</th><th>Damage</th><th>Severity</th><th>Note</th></tr></thead><tbody>${markerRows || "<tr><td colspan='6'>No markers</td></tr>"}</tbody></table></body></html>`);
        report.document.close();
        report.focus();
        report.print();
      });
    }

    const saveJobButton = wrapper.querySelector("[data-idr-save-field-job]");
    if (saveJobButton) {
      saveJobButton.addEventListener("click", async () => {
        saveJobButton.disabled = true;
        await saveFieldJob();
        saveJobButton.disabled = false;
      });
    }

    const uploadButton = wrapper.querySelector("[data-idr-upload-field-photos]");
    if (uploadButton) {
      uploadButton.addEventListener("click", async () => {
        uploadButton.disabled = true;
        try {
          await uploadFieldPhotos();
        } catch (error) {
          syncPanel("error", "Photo upload blocked", error.message || "The photos could not be uploaded.");
        }
        uploadButton.disabled = false;
      });
    }

    if (config.sampleHouse) {
      state.photos.push({
        id: "P-001",
        name: "sample-contractor-house-reference.png",
        size: 0,
        url: config.sampleHouse,
        sample: true,
      });
      state.active = 0;
    }

    renderAll();
  }

  wrappers.forEach((wrapper) => {
    const api = wrapper.getAttribute("data-flight-api");
    const fallback = {
      missionId: "IDP-VISION-FALLBACK",
      generatedBy: "Inspector DroneProof Vision API",
      fallbackReason: "Fallback loaded locally when the live route is unavailable.",
      model: {
        googleMapsConfigured: false,
        openAiConfigured: false,
      },
      flightInstructions: [
        { id: "WP-01", action: "Claim overview", altitudeFt: 92, cameraPitchDeg: -58, targetPlane: "ALL", capture: "Four-corner roof overview plus street-facing context." },
        { id: "WP-02", action: "Front slope grid", altitudeFt: 54, cameraPitchDeg: -72, targetPlane: "A", capture: "Left-to-right image row with consistent overlap." },
        { id: "WP-03", action: "Ridge and vent detail", altitudeFt: 42, cameraPitchDeg: -48, targetPlane: "A/B", capture: "Ridge caps, vents, flashing, and soft-metal reference." },
        { id: "WP-04", action: "Rear slope grid", altitudeFt: 56, cameraPitchDeg: -72, targetPlane: "B", capture: "Rear plane sweep with gutter and valley context." },
        { id: "WP-05", action: "Garage return", altitudeFt: 44, cameraPitchDeg: -68, targetPlane: "C", capture: "Garage plane, tie-in, gutter line, and collateral notes." },
      ],
    };

    function usePlan(plan) {
      setupModes(wrapper);
      setupPlanner(wrapper, plan);
      setupPhotoLab(wrapper);
      startRenderer(wrapper, plan);
    }

    if (!api) {
      usePlan(fallback);
      return;
    }

    fetch(api)
      .then((response) => {
        if (!response.ok) throw new Error("Bad response");
        return response.json();
      })
      .then(usePlan)
      .catch(() => usePlan(fallback));
  });
})();
