let scene, camera, renderer, controls, raycaster, directionalLight, ambientLight;
let model,
  ground,
  grid,
  selectedPart = null,
  defaultModel = null;
let currentLoader; // GLTFLoader المستخدم لتحميل النماذج
const textureLoader = new THREE.TextureLoader(); // لتحميل القوام (الكود الأول)
const loader = new THREE.TextureLoader(); // لتحميل القوام (الكود الثاني)
const container = document.getElementById("threejs-container");
let fabricTexture;
let printMesh = null;
let rotationSpeed = 0;
let mouse = new THREE.Vector2();
let capturerCCapture = null;

// متغيرات الحركة
let swing = false,
  hover = false,
  jump = false;
// مصفوفة النماذج – قم بتعديلها لتطابق أسماء المجلدات الفرعية لديك
const models = [
  {
    folder: "crewneck-sweatshirt-mockup",
    name: "crewneck-sweatshirt-mockup"
  },
  { folder: "full-zip-hoodie-mockup", name: "full-zip-hoodie-mockup" },
  {
    folder: "mens-crewneck-sweatshirt-mockup",
    name: "mens-crewneck-sweatshirt-mockup"
  },
  {
    folder: "long-sleeve-v-neck-shirt-mockup",
    name: "long-sleeve-v-neck-shirt-mockup"
  },
  {
    folder: "mens-round-neck-sweatshirt-mockup",
    name: "mens-round-neck-sweatshirt-mockup"
  },
  { folder: "Oversized-hoodie", name: "Oversized-hoodie" },
  { folder: "Oversized-hoodie-mockup", name: "Oversized-hoodie-mockup" },
  { folder: "Raglan-hoodie-mockup", name: "Raglan-hoodie-mockup" },
  { folder: "Raglan-t-shirt-mockup", name: "Raglan-t-shirt-mockup" },
  {
    folder: "Round-neck-long-sleeve-raglan-t-shirt",
    name: "Round-neck-long-sleeve-raglan-t-shirt"
  },
  {
    folder: "round-neck-long-sleeve-raglan-t-shirt-mockup",
    name: "round-neck-long-sleeve-raglan-t-shirt-mockup"
  },
  {
    folder: "Short-sleeve-cropped-hoodie",
    name: "Short-sleeve-cropped-hoodie"
  },
  {
    folder: "Sleeveless-hoodie-mockup",
    name: "Sleeveless-hoodie-mockup"
  },
  {
    folder: "Sleeveless-zip-hoodie-mockup",
    name: "Sleeveless-zip-hoodie-mockup"
  },
  { folder: "Tank-top-mockup", name: "Tank-top-mockup" },
  { folder: "Tshirt-mockup", name: "Tshirt-mockup" },
  { folder: "Women's-crop-tank-top", name: "Women's-crop-tank-top" },
  {
    folder: "Women's-deep-v-neck-tshirt",
    name: "Women's-deep-v-neck-tshirt"
  },
  { folder: "Women's-hoodie-mockup", name: "Women's-hoodie-mockup" },
  {
    folder: "Women's-slim-fit-v-neck-tshirt",
    name: "Women's-slim-fit-v-neck-tshirt"
  },
  { folder: "Women's-tank-top", name: "Women's-tank-top" },
  { folder: "Women's-tank-top-mockup", name: "Women's-tank-top-mockup" },
  { folder: "Women's-tshirt", name: "Women's-tshirt" },
  { folder: "womens-t-shirt-mockup", name: "womens-t-shirt-mockup" },
  { folder: "Women-tank-top-mockup", name: "Women-tank-top-mockup" },
  { folder: "0", name: "0" },
  {
    folder: "Modelled-boxy-hoodie-mockup",
    name: "Modelled-boxy-hoodie-mockup"
  },
  { folder: "Womens-tshirt", name: "Womens-tshirt" },
  {
    folder: "sweatshirt-womens-mockup",
    name: "sweatshirt-womens-mockup"
  },
  { folder: "womens-tshirt-mockup", name: "womens-tshirt-mockup" }
];

// تهيئة currentLoader لاستخدام GLTFLoader (لتفعيل التحميل الديناميكي)
currentLoader = new THREE.GLTFLoader();

/**
 * تحميل النموذج ثلاثي الأبعاد من مجلد محدد وإضافته إلى المشهد.
 * - يقوم بتعيين المواد، توسيط النموذج، وضبط الإعدادات الافتراضية.
 * @param {string} folderName اسم مجلد النموذج
 */
function loadModel(folderName) {
  if (model) {
    scene.remove(model);
  }
  currentLoader.load(
    `models/${folderName}/model.gltf`,
    function (gltf) {
      model = gltf.scene;

      // مسح التدوير والموضع الأولي للنموذج
      model.rotation.set(0, 0, 0);
      model.position.set(0, 0, 0);

      // حساب صندوق الإحاطة (Box3) لضبط حجم النموذج بشكل مناسب
      const box = new THREE.Box3().setFromObject(model);
      const size = new THREE.Vector3();
      box.getSize(size);
      const maxDim = Math.max(size.x, size.y, size.z);
      const scaleFactor = 3 / maxDim;
      model.scale.set(scaleFactor, scaleFactor, scaleFactor);

      // توسيط النموذج بشكل دقيق في منتصف المشهد
      box.setFromObject(model);
      const center = new THREE.Vector3();
      box.getCenter(center);
      model.position.copy(center).multiplyScalar(-1);

      // رفع النموذج للأعلى لتجنب التداخل مع حاوية image-editor-container
      // نظرًا لأن ارتفاع threejs-container هو 70% والنموذج يجب أن يظهر في منتصفه
      model.position.y = 1.0; // زيادة قيمة الارتفاع للأعلى

      // ضبط المواد لكل جزء من النموذج
      model.traverse((child) => {
        if (child.isMesh) {
          child.castShadow = true;
          if (child.name.toLowerCase().includes("print")) {
            printMesh = child;
            child.material = new THREE.MeshStandardMaterial({
              color: 0xffffff,
              metalness: 0.01,
              roughness: 0.1,
              envMapIntensity: 0.1,
              map: fabricTexture || null
            });
          } else {
            child.material = new THREE.MeshStandardMaterial({
              color: 0xffffff,
              metalness: 0.01,
              roughness: 0.1,
              envMapIntensity: 0.1
            });
          }
        }
      });

      // إضافة النموذج للمشهد
      scene.add(model);

      // تعيين عناصر التحكم لتستهدف مركز النموذج
      controls.target.set(0, model.position.y, 0); // تعديل هدف الكاميرا ليكون مركز النموذج
      controls.update();

      defaultModel = model.clone();
    },
    undefined,
    (error) => {
      console.error("خطأ في تحميل النموذج من المجلد", folderName, error);
    }
  );
}

// دالة لتوسيط النموذج في المشهد
function centerModelInScene() {
  if (!model) return;

  // مسح التدوير والموضع الأولي
  model.position.set(0, 0, 0);

  // حساب حجم وأبعاد النموذج
  const box = new THREE.Box3().setFromObject(model);
  const size = new THREE.Vector3();
  const center = new THREE.Vector3();

  box.getSize(size);
  box.getCenter(center);

  // توسيط النموذج وفقًا للمركز المحسوب
  model.position.copy(center).multiplyScalar(-1);

  // رفع النموذج للأعلى لتجنب التداخل مع حاوية image-editor-container
  // نظرًا لأن ارتفاع threejs-container هو 70% والنموذج يجب أن يظهر في منتصفه
  model.position.y += 1.0; // زيادة قيمة الارتفاع للأعلى

  // تحديث عناصر التحكم
  controls.target.set(0, model.position.y, 0); // تعديل هدف الكاميرا ليكون مركز النموذج
  controls.update();
}

// تحميل النموذج الافتراضي من أول عنصر في المصفوفة
loadModel("0");

const skyboxFolders = [
  "skybox/bologni/",
  "skybox/Cloudy/",
  "skybox/DallasW/",
  "skybox/entrance/",
  "skybox/FullMoon/",
  "skybox/farm/",
  "skybox/hotel/",
  "skybox/lebombo/",
  "skybox/Marriott/",
  "skybox/photostudio/",
  "skybox/darker/",
  "skybox/skyboxsun/",
  "skybox/spruit/",
  "skybox/sun/",
  "skybox/SunSet/",
  "skybox/CloudsWater/",
  "skybox/Tropical/",
  "skybox/Vasa/",
  "skybox/veranda/",
  "skybox/Stormy/"
];
const texturePaths = [
  "textures/black.jpg",
  "textures/black1.jpg",
  "textures/black2.jpg",
  "textures/wood1.jpg",
  "textures/wood2.jpg",
  "textures/wood3.jpg",
  "textures/wood4.jpg",
  "textures/wood5.jpg",
  "textures/silver.jpg",
  "textures/rock.jpg",
  "textures/paper.jpg",
  "textures/textil.jpg",
  "textures/wood6.jpg",
  "textures/color.jpg",
  "textures/marble.jpg",
  "textures/marble2.jpg",
  "textures/marble3.jpg",
  "textures/marble4.jpg",
  "textures/marble5.jpg",
  "textures/color.jpg",
  "textures/marble.jpg",
  "textures/marble2.jpg",
  "textures/marble3.jpg",
  "textures/marble4.jpg",
  "textures/gold.jpg",
  "textures/gold1.jpg",
  "textures/blue.jpg",
  "textures/blue1.jpg",
  "textures/blue2.jpg",
  "textures/blue3.jpg",
  "textures/gold2.jpg",
  "textures/gold3.jpg",
  "textures/gold4.jpg",
  "textures/gold5.jpg",
  "textures/gold6.jpg",
  "textures/blue3.jpg",
  "textures/gold1.jpg",
  "textures/marble5.jpg",
  "textures/marble1.jpg",
  "textures/marble6.jpg",
  "textures/reflectiv.jpg",
  "textures/silver1.jpg",
  "textures/silver2.jpg",
  "textures/silver3.jpg",
  "textures/silver4.jpg",
  "textures/silver5.jpg",
  "textures/white1.jpg",
  "textures/wood.jpg",
  "textures/wood6.jpg",
  "textures/silver4.jpg"
];
function loadSkybox(folder) {
  const cubeTextureLoader = new THREE.CubeTextureLoader();
  cubeTextureLoader.setPath(folder);

  cubeTextureLoader.load(
    ["px.jpg", "nx.jpg", "py.jpg", "ny.jpg", "pz.jpg", "nz.jpg"],
    (texture) => {
      scene.background = texture;
    },
    undefined,
    (error) => {
      console.error("خطأ في تحميل خلفية السماء:", error);
      scene.background = new THREE.Color(0x344464);
    }
  );
}

function showSkyboxThumbnails() {
  const thumbnailsContainer = document.querySelector("#skybox-thumbnails .skybox-grid");
  thumbnailsContainer.innerHTML = "";

  skyboxFolders.forEach((folder, index) => {
    const img = document.createElement("img");
    img.src = folder + "px.jpg";
    img.alt = `خلفية السماء ${index + 1}`;
    img.onclick = () => {
      loadSkybox(folder);
      document.getElementById("skybox-thumbnails").classList.remove("active");
    };
    thumbnailsContainer.appendChild(img);
  });

  document.getElementById("skybox-thumbnails").classList.add("active");
}

function showTextureThumbnails() {
  const thumbnailsContainer = document.querySelector("#texture-thumbnails .texture-grid");
  thumbnailsContainer.innerHTML = "";

  texturePaths.forEach((path, index) => {
    const img = document.createElement("img");
    img.src = path;
    img.alt = `القوام ${index + 1}`;
    img.onclick = () => {
      applyTexture(path);
      document.getElementById("texture-thumbnails").classList.remove("active");
    };
    thumbnailsContainer.appendChild(img);
  });

  document.getElementById("texture-thumbnails").classList.add("active");
}

function applyTexture(texturePath) {
  const texture = loader.load(texturePath);
  if (selectedPart) {
    if (!selectedPart.material.isCloned) {
      selectedPart.material = selectedPart.material.clone();
      selectedPart.material.isCloned = true;
    }
    selectedPart.material.color.set("#ffffff");
    selectedPart.material.map = texture;
    selectedPart.material.needsUpdate = true;
  } else {
    scene.background = texture;
  }
}

document.getElementById("show-skybox-thumbnails").addEventListener("click", showSkyboxThumbnails);

document.getElementById("show-texture-thumbnails").addEventListener("click", showTextureThumbnails);

function initScene() { // Renamed from init to initScene to avoid conflicts
  scene = new THREE.Scene();
  scene.background = new THREE.Color(0x1f2f52);

  // الحصول على أبعاد حاوية threejs-container
  const container = document.getElementById("threejs-container");
  const containerWidth = container.clientWidth;
  const containerHeight = container.clientHeight;

  camera = new THREE.PerspectiveCamera(70, containerWidth / containerHeight, 1, 1000);
  // تعديل موضع الكاميرا لتكون أعلى وتنظر للأسفل قليلاً
  camera.position.set(-3, 3, 5);

  renderer = new THREE.WebGLRenderer({
    antialias: true,
    canvas: document.getElementById("canvas")
  });
  renderer.setSize(containerWidth, containerHeight);
  renderer.shadowMap.enabled = true;
  document.body.appendChild(renderer.domElement);

  controls = new THREE.OrbitControls(camera, renderer.domElement);
  controls.enableDamping = true;
  controls.dampingFactor = 0.05;
  controls.minDistance = 5;
  controls.maxDistance = 50;

  controls.minPolarAngle = Math.PI / 8;
  controls.maxPolarAngle = Math.PI / 2.5;
  // زاوية قصوى
  raycaster = new THREE.Raycaster();
  mouse = new THREE.Vector2();

  const groundGeometry = new THREE.BoxGeometry(10, 10, 0.5);
  const groundMaterial = new THREE.MeshStandardMaterial({
    color: 0xe0e5eb
  });
  ground = new THREE.Mesh(groundGeometry, groundMaterial);
  ground.rotation.x = -Math.PI / 2;
  // رفع الأرضية لتتناسب مع موضع النموذج الجديد
  ground.position.y = -0.5; // تعديل موضع الأرضية لتكون أعلى
  ground.receiveShadow = true;
  scene.add(ground);

  const gridColor = new THREE.Color("#d3a77b");
  const grid = new THREE.GridHelper(9, 9, gridColor, gridColor);
  // رفع الشبكة لتتناسب مع موضع النموذج الجديد
  grid.position.y = -0.5; // تعديل موضع الشبكة لتكون أعلى
  grid.rotation.y = THREE.MathUtils.degToRad(90);
  scene.add(grid);

  const toggler = document.getElementById("toggler");
  const toggleVisibility = document.getElementById("toggleVisibility");

  let isGridSelected = true;

  toggler.addEventListener("click", () => {
    isGridSelected = !isGridSelected;
    if (isGridSelected) {
      toggler.textContent = "ارضية";
      grid.visible = toggleVisibility.checked;
      ground.visible = false;
    } else {
      toggler.textContent = "شبكة";
      grid.visible = false;
      ground.visible = toggleVisibility.checked;
    }
  });

  toggleVisibility.addEventListener("change", () => {
    if (isGridSelected) {
      grid.visible = toggleVisibility.checked;
    } else {
      ground.visible = toggleVisibility.checked;
    }
  });

  directionalLight = new THREE.DirectionalLight(0xffffff, 0.45);
  directionalLight.position.set(5, 5, 5);
  directionalLight.castShadow = true;
  scene.add(directionalLight);

  ambientLight = new THREE.AmbientLight(0x969696, 1);
  scene.add(ambientLight);

  const backLight = new THREE.DirectionalLight(0xcecece, 0.5);
  backLight.position.set(-1, 1, -1);
  scene.add(backLight);

  window.addEventListener("resize", onWindowResize);
  window.addEventListener("dblclick", onDoubleClick);

  setupSidebar();
  function onWindowResize() {
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
    renderer.setSize(window.innerWidth, window.innerHeight);

    // إعادة توسيط النموذج بعد تغيير الحجم
    if (model) {
      centerModelInScene();
    }
  }

  document.addEventListener("click", function (event) {
    const controls = document.querySelectorAll(".controls");
    controls.forEach((control) => {
      if (!control.contains(event.target)) {
        control.style.display = "none";
      }
    });
  });

  document.getElementById("reset-model").addEventListener("click", function () {
    const originalPosition = model.position.clone();
    const originalRotation = model.rotation.clone();
    const originalScale = model.scale.clone();

    scene.remove(model);

    model = defaultModel.clone();

    model.position.copy(originalPosition);
    model.rotation.copy(originalRotation);
    model.scale.copy(originalScale);

    model.traverse(function (child) {
      if (child.isMesh) {
        child.material = new THREE.MeshStandardMaterial({
          color: 0xffffff,
          metalness: 0.01,
          roughness: 0.1,
          envMapIntensity: 0.1
        });
      }
    });

    scene.add(model);

    controls.update();
  });

  document.getElementById("reset-ground").addEventListener("click", function () {
    ground.material.color.set(0xe7ecf3);
    ground.material.map = null;
    ground.material.needsUpdate = true;
  });

  document.getElementById("reset-background").addEventListener("click", function () {
    scene.background = new THREE.Color(0x182949);
  });
}

// تحديث حجم المُعرض عند تغيير حجم النافذة
function onWindowResize() {
  const container = document.getElementById("threejs-container");
  const width = container.clientWidth;
  const height = container.clientHeight;

  camera.aspect = width / height;
  camera.updateProjectionMatrix();
  renderer.setSize(width, height);

  // إعادة توسيط النموذج بعد تغيير الحجم
  if (model) {
    centerModelInScene();
  }
}
window.addEventListener("resize", onWindowResize, false);

function onDoubleClick(event) {
  mouse.x = (event.clientX / window.innerWidth) * 2 - 1;
  mouse.y = -(event.clientY / window.innerHeight) * 2 + 1;

  raycaster.setFromCamera(mouse, camera);

  const intersects = raycaster.intersectObjects([model, ground], true);

  if (intersects.length > 0) {
    const selectedObject = intersects[0].object;

    document.querySelectorAll(".controls").forEach((control) => {
      control.style.display = "none";
    });

    if (selectedObject === ground) {
      selectedPart = ground;
      document.getElementById("ground-controls").style.display = "block";
      document.getElementById("ground-controls").style.left = `${event.clientX}px`;
      document.getElementById("ground-controls").style.top = `${event.clientY}px`;
    } else if (selectedObject === model || isPartOfModel(selectedObject)) {
      selectedPart = selectedObject;
      document.getElementById("model-controls").style.display = "block";
      document.getElementById("model-controls").style.left = `${event.clientX}px`;
      document.getElementById("model-controls").style.top = `${event.clientY}px`;
    }
  } else {
    document.getElementById("background-controls").style.display = "block";
    document.getElementById("background-controls").style.left = `${event.clientX}px`;
    document.getElementById("background-controls").style.top = `${event.clientY}px`;
  }
}

function isPartOfModel(object) {
  let parent = object.parent;
  while (parent !== null) {
    if (parent === model) {
      return true;
    }
    parent = parent.parent;
  }
  return false;
}

function animateScene() { // Renamed from animate to animateScene to avoid conflicts
  requestAnimationFrame(animateScene);
  controls.update();
  if (rotationSpeed !== 0 && model) {
    model.rotation.y += rotationSpeed;
  }

  if (fabricTexture) {
    fabricTexture.needsUpdate = true;
    if (printMesh) {
      printMesh.material.map = fabricTexture;
      printMesh.material.needsUpdate = true;
    }
  }

  // تطبيق تأثيرات الحركة
  if (swing && model) {
    model.rotation.z = 0.1 * Math.sin(Date.now() * 0.005);
  }
  if (hover && model) {
    model.position.y = 0.2 * Math.sin(Date.now() * 0.005);
  }
  if (jump && model) {
    model.position.y = 0.5 * Math.abs(Math.sin(Date.now() * 0.1));
  }

  renderer.render(scene, camera);
  if (capturerCCapture) {
    capturerCCapture.capture(renderer.domElement);
  }
}

function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  sidebar.classList.toggle("active");
}

function setupSidebar() {
  document.getElementById("light-vertical").addEventListener("input", (e) => {
    directionalLight.position.y = parseFloat(e.target.value);
  });

  document.getElementById("table-light").addEventListener("input", (e) => {
    directionalLight.intensity = parseFloat(e.target.value);
  });

  document.getElementById("fov").addEventListener("input", (e) => {
    camera.fov = parseFloat(e.target.value) * 100;
    camera.updateProjectionMatrix();
  });

  document.getElementById("color").addEventListener("input", (e) => {
    const color = new THREE.Color(e.target.value);
    model.traverse((child) => {
      if (child.isMesh) {
        child.material.color = color;
      }
    });
  });

  document.getElementById("metalness").addEventListener("input", (e) => {
    model.traverse((child) => {
      if (child.isMesh) {
        child.material.metalness = parseFloat(e.target.value);
      }
    });
  });

  document.getElementById("roughness").addEventListener("input", (e) => {
    model.traverse((child) => {
      if (child.isMesh) {
        child.material.roughness = parseFloat(e.target.value);
      }
    });
  });

  document.getElementById("env-intensity").addEventListener("input", (e) => {
    model.traverse((child) => {
      if (child.isMesh) {
        child.material.envMapIntensity = parseFloat(e.target.value);
      }
    });
  });

  document.getElementById("model-x").addEventListener("input", (e) => {
    model.position.x = parseFloat(e.target.value);
  });

  document.getElementById("model-y").addEventListener("input", (e) => {
    model.position.y = parseFloat(e.target.value);
  });

  document.getElementById("model-z").addEventListener("input", (e) => {
    model.position.z = parseFloat(e.target.value);
  });

  document.getElementById("ground-y").addEventListener("input", (e) => {
    ground.position.y = parseFloat(e.target.value);
  });

  document.getElementById("camera-x").addEventListener("input", (e) => {
    camera.position.x = parseFloat(e.target.value);
  });

  document.getElementById("camera-y").addEventListener("input", (e) => {
    camera.position.y = parseFloat(e.target.value);
  });

  document.getElementById("camera-z").addEventListener("input", (e) => {
    camera.position.z = parseFloat(e.target.value);
  });
}

// Initial calls
initScene();
animateScene();

// استدعاء دالة تغيير الحجم وتوسيط النموذج بعد تحميل الصفحة
window.addEventListener("load", function () {
  onWindowResize();

  // ضمان تحديث النموذج بعد تحميل الصفحة بالكامل
  setTimeout(function () {
    if (model) {
      centerModelInScene();
    }
  }, 500);
});

function hideAllControls() {
  document.querySelectorAll(".controls").forEach((control) => {
    control.style.display = "none";
  });
}

function showControl(controlId, x, y) {
  hideAllControls();
  const control = document.getElementById(controlId);
  control.style.left = x + "px";
  control.style.top = y + "px";
  control.style.display = "block";
}

function handleInteraction(clientX, clientY) {
  mouse.x = (clientX / window.innerWidth) * 2 - 1;
  mouse.y = -(clientY / window.innerHeight) * 2 + 1;
  raycaster.setFromCamera(mouse, camera);

  const intersects = raycaster.intersectObjects([model, ground], true);
  if (intersects.length > 0) {
    const selectedObject = intersects[0].object;
    hideAllControls();
    if (selectedObject === ground) {
      selectedPart = ground;
      showControl("ground-controls", clientX, clientY);
    } else if (selectedObject === model || isPartOfModel(selectedObject)) {
      selectedPart = selectedObject;
      showControl("model-controls", clientX, clientY);
    }
  } else {
    hideAllControls();
    showControl("background-controls", clientX, clientY);
  }
}

document.addEventListener("dblclick", onDoubleClick, false);

const hammer = new Hammer(document.body);
hammer.get("doubletap").set({ taps: 2 });
hammer.get("press").set({ time: 500 });
hammer.on("doubletap press", function (ev) {
  handleInteraction(ev.center.x, ev.center.y);
});

document.getElementById("model-color-picker").addEventListener("input", function (event) {
  const color = event.target.value;
  if (selectedPart) {
    if (!selectedPart.material.isCloned) {
      selectedPart.material = selectedPart.material.clone();
      selectedPart.material.isCloned = true;
    }
    selectedPart.material.color.set(color);
  }
});

document.getElementById("model-texture-picker").addEventListener("click", function () {
  document.getElementById("model-texture-input").click();
});

document.getElementById("model-texture-input").addEventListener("change", function (event) {
  const file = event.target.files[0];
  if (file && selectedPart) {
    const reader = new FileReader();
    reader.onload = function (e) {
      loader.load(e.target.result, function (texture) {
        texture.flipY = false;
        if (!selectedPart.material.isCloned) {
          selectedPart.material = selectedPart.material.clone();
          selectedPart.material.isCloned = true;
        }
        selectedPart.material.color.set("#ffffff");
        selectedPart.material.map = texture;
        selectedPart.material.needsUpdate = true;
      });
    };
    reader.readAsDataURL(file);
  }
});

const groundColorPicker = document.getElementById("ground-color-picker");

function updateGroundColor() {
  const color = groundColorPicker.value;
  ground.material.color.set(color);
}

groundColorPicker.addEventListener("input", updateGroundColor);
groundColorPicker.addEventListener("change", updateGroundColor);
groundColorPicker.addEventListener("touchend", function () {
  setTimeout(updateGroundColor, 100);
});

document.getElementById("ground-texture-picker").addEventListener("click", function () {
  document.getElementById("ground-texture-input").click();
});

document.getElementById("ground-texture-input").addEventListener("change", function (event) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function (e) {
      loader.load(e.target.result, function (texture) {
        texture.flipY = true;
        ground.material.map = texture;
        ground.material.needsUpdate = true;
      });
    };
    reader.readAsDataURL(file);
  }
});

document.getElementById("background-color-picker").addEventListener("input", function (event) {
  const color = event.target.value;
  scene.background = new THREE.Color(color);
});

document.getElementById("background-image-picker").addEventListener("click", function () {
  document.getElementById("background-image-input").click();
});

document.getElementById("background-image-input").addEventListener("change", function (event) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = function (e) {
      loader.load(e.target.result, function (texture) {
        texture.flipY = true;
        scene.background = texture;
      });
    };
    reader.readAsDataURL(file);
  }
});

document.getElementById("reset-model").addEventListener("click", function () {
  const originalPosition = model.position.clone();
  const originalRotation = model.rotation.clone();
  const originalScale = model.scale.clone();

  scene.remove(model);

  model = defaultModel.clone();

  model.position.copy(originalPosition);
  model.rotation.copy(originalRotation);
  model.scale.copy(originalScale);

  model.traverse(function (child) {
    if (child.isMesh) {
      child.material = new THREE.MeshStandardMaterial({
        color: 0xffffff,
        metalness: 0.01,
        roughness: 0.1,
        envMapIntensity: 0.2
      });
    }
  });

  scene.add(model);
});

document.getElementById("reset-ground").addEventListener("click", function () {
  ground.material.color.set(0xffffff);
  ground.material.map = null;
  ground.material.needsUpdate = true;
});

document.getElementById("reset-background").addEventListener("click", function () {
  scene.background = new THREE.Color(0x182949);
});

// =============================
// تكامل fabric.js مع الوظائف المطلوبة (النصوص، الصور والفلاتر)
// =============================
let fabricCanvas;

let croppingRect = null;
let selectedObject = null;
let isCropping = false;

// Set up event listeners
document.getElementById("startCrop").addEventListener("click", startCropping);
document.getElementById("crop").addEventListener("click", executeCrop);

function startCropping() {
  // Get the active object on canvas
  selectedObject = fabricCanvas.getActiveObject();

  if (!selectedObject) {
    alert("يرجى تحديد صورة على القماش أولاً");
    return;
  }

  if (selectedObject.type !== "image") {
    alert("يمكن قص الصور فقط");
    return;
  }

  isCropping = true;

  // Create cropping rectangle
  croppingRect = new fabric.Rect({
    width: selectedObject.width * selectedObject.scaleX,
    height: selectedObject.height * selectedObject.scaleY,
    left: selectedObject.left,
    top: selectedObject.top,
    fill: "rgba(0,0,0,0.3)",
    stroke: "#d3a77b",
    strokeWidth: 2,
    strokeDashArray: [5, 5],
    selectable: true,
    hasControls: true,
    lockRotation: true
  });

  fabricCanvas.add(croppingRect);
  fabricCanvas.setActiveObject(croppingRect);
  document.getElementById("crop").style.display = "inline-block";
}

function executeCrop() {
  if (!croppingRect || !selectedObject) return;

  // Calculate crop dimensions
  const scale = 1 / selectedObject.scaleX;
  const cropX = (croppingRect.left - selectedObject.left) * scale;
  const cropY = (croppingRect.top - selectedObject.top) * scale;
  const cropWidth = croppingRect.width * scale;
  const cropHeight = croppingRect.height * scale;

  // Create cropped image
  fabric.Image.fromURL(selectedObject.getSrc(), function (img) {
    img.set({
      left: croppingRect.left,
      top: croppingRect.top,
      cropX: cropX,
      cropY: cropY,
      width: cropWidth,
      height: cropHeight
    });

    fabricCanvas.remove(selectedObject);
    fabricCanvas.remove(croppingRect);
    fabricCanvas.add(img);
    fabricCanvas.renderAll();

    // Reset cropping state
    isCropping = false;
    croppingRect = null;
    selectedObject = null;
    document.getElementById("crop").style.display = "none";
  });
}

let imageObj; // Track the main image object on the Fabric canvas
const stateStack = [];
let currentStateIndex = -1;

function saveState() {
  if (!fabricCanvas) return;
  const json = fabricCanvas.toJSON();
  // Remove any redo history once a new operation is recorded
  stateStack.splice(currentStateIndex + 1);
  stateStack.push(json);
  currentStateIndex++;
}

function loadState(index) {
  if (index >= 0 && index < stateStack.length) {
    fabricCanvas.loadFromJSON(stateStack[index], () => {
      fabricCanvas.renderAll();
      // update reference to first image object if available
      imageObj = fabricCanvas.getObjects().find((obj) => obj.type === "image");
    });
  }
}

// Automatically keep history whenever objects are added or modified
if (typeof fabricCanvas !== "undefined") {
  fabricCanvas.on("object:modified", saveState);
  fabricCanvas.on("object:added", saveState);
}

function initFabricCanvas() {
  if (!fabricCanvas) {
    const editorContainer = document.getElementById("image-editor-container");
    const editorHeight = editorContainer.offsetHeight;
    fabricCanvas = new fabric.Canvas("3dcanvas", {
      width: window.innerWidth,
      height: editorHeight,
      backgroundColor: "rgba(255,255,255,0.8)"
    });
    fabricTexture = new THREE.CanvasTexture(fabricCanvas.lowerCanvasEl);
    fabricTexture.flipY = false;
    if (printMesh) {
      printMesh.material.map = fabricTexture;
      printMesh.material.needsUpdate = true;
    }

    // Initialize FabricHistory with the canvas
    if (typeof App !== "undefined" && App.FabricHistory) {
      App.FabricHistory.init(fabricCanvas);

      // Connect undo/redo buttons
      const undoBtn = document.getElementById("undoBtn");
      const redoBtn = document.getElementById("redoBtn");

      if (undoBtn) {
        undoBtn.addEventListener("click", function () {
          App.FabricHistory.undo();
        });
      }

      if (redoBtn) {
        redoBtn.addEventListener("click", function () {
          App.FabricHistory.redo();
        });
      }

      // Add keyboard shortcuts
      document.addEventListener("keydown", function (e) {
        // Check for Ctrl+Z (undo) or Cmd+Z on Mac
        if ((e.ctrlKey || e.metaKey) && e.key === "z") {
          if (e.shiftKey) {
            // Ctrl+Shift+Z or Cmd+Shift+Z for redo
            App.FabricHistory.redo();
          } else {
            // Ctrl+Z or Cmd+Z for undo
            App.FabricHistory.undo();
          }
          e.preventDefault();
        }
        // Check for Ctrl+Y for redo (Windows/Linux)
        else if (e.ctrlKey && e.key === "y") {
          App.FabricHistory.redo();
          e.preventDefault();
        }
      });
    }
    fabricCanvas.on("selection:created", function (e) {
      const activeObject = fabricCanvas.getActiveObject();
      if (activeObject) {
        if (activeObject.type === "i-text") {
          document.getElementById("floating-menu").style.display = "block";
          document.getElementById("floating-menu").style.display = "none";
        } else if (activeObject.type === "image") {
          document.getElementById("floating-menu").style.display = "block";
          document.getElementById("floating-menu").style.display = "none";
        }
      }
    });
    fabricCanvas.on("selection:updated", function (e) {
      const activeObject = fabricCanvas.getActiveObject();
      if (activeObject) {
        if (activeObject.type === "i-text") {
          document.getElementById("floating-menu").style.display = "block";
          document.getElementById("floating-menu").style.display = "none";
        } else if (activeObject.type === "image") {
          document.getElementById("floating-menu").style.display = "block";
          document.getElementById("floating-menu").style.display = "none";
        }
      }
    });
    fabricCanvas.on("selection:cleared", function () {
      document.getElementById("floating-menu").style.display = "none";
      document.getElementById("floating-menu").style.display = "none";
    });
  }
}

function showFabricEditor() {
  const editorContainer = document.getElementById("image-editor-container");
  if (editorContainer.style.display !== "block") {
    editorContainer.style.display = "block";
    initFabricCanvas();
  }
}
// =============================
// Fabric.js History Management
// =============================

// Initialize FabricHistory namespace
window.App = window.App || {};

App.FabricHistory = (function () {
  let canvas;
  const undoStack = [];
  const redoStack = [];
  const maxHistory = 20; // Reduced from 50 to save memory

  // Initialize with fabric canvas
  function init(fabricCanvas) {
    canvas = fabricCanvas;

    // Set up event listeners
    setupEventListeners();

    // Save initial state
    saveState();
  }

  // Set up event listeners for canvas changes
  function setupEventListeners() {
    if (!canvas) return;

    // Save state on important events
    canvas.on("object:added", saveState);
    canvas.on("object:modified", saveState);
    canvas.on("object:removed", saveState);

    // Save state on object transformations
    canvas.on("object:moving", saveState);
    canvas.on("object:scaling", saveState);
    canvas.on("object:rotating", saveState);
    canvas.on("object:skewing", saveState);
  }

  // Save current canvas state
  function saveState() {
    if (!canvas) return;

    // Don't save state during undo/redo operations
    if (window.isUndoRedoInProgress) return;

    try {
      const json = canvas.toJSON(["selectable", "evented"]);
      undoStack.push(json);

      // Limit stack size
      if (undoStack.length > maxHistory) {
        undoStack.shift();
      }

      // Clear redo stack when a new action is performed
      redoStack.length = 0;

      updateButtonStates();
    } catch (error) {
      console.error("Error saving canvas state:", error);
    }
  }

  // Undo last action
  function undo() {
    if (undoStack.length < 2) return; // Need at least 2 states to undo

    try {
      window.isUndoRedoInProgress = true;

      // Save current state to redo stack
      const currentState = undoStack.pop();
      redoStack.push(currentState);

      // Get the previous state
      const previousState = undoStack[undoStack.length - 1];

      // Apply the previous state
      if (previousState) {
        canvas.loadFromJSON(previousState, function () {
          canvas.renderAll();
          updateButtonStates();
          window.isUndoRedoInProgress = false;
        });
      }
    } catch (error) {
      console.error("Error during undo:", error);
      window.isUndoRedoInProgress = false;
    }
  }

  // Redo last undone action
  function redo() {
    if (redoStack.length === 0) return;

    try {
      window.isUndoRedoInProgress = true;

      // Get the next state
      const nextState = redoStack.pop();

      // Apply the next state
      if (nextState) {
        undoStack.push(nextState);
        canvas.loadFromJSON(nextState, function () {
          canvas.renderAll();
          updateButtonStates();
          window.isUndoRedoInProgress = false;
        });
      }
    } catch (error) {
      console.error("Error during redo:", error);
      window.isUndoRedoInProgress = false;
    }
  }

  // Update button states based on stack sizes
  function updateButtonStates() {
    const undoBtn = document.getElementById("undoBtn");
    const redoBtn = document.getElementById("redoBtn");

    if (undoBtn) {
      undoBtn.disabled = undoStack.length < 2;
      undoBtn.style.opacity = undoStack.length < 2 ? "0.5" : "1";
    }

    if (redoBtn) {
      redoBtn.disabled = redoStack.length === 0;
      redoBtn.style.opacity = redoStack.length === 0 ? "0.5" : "1";
    }
  }

  return {
    init: init,
    saveState: saveState,
    undo: undo,
    redo: redo
  };
})();

// Initialize when document is ready
document.addEventListener("DOMContentLoaded", function () {
  // Initialize FabricHistory with the fabric canvas
  if (typeof fabricCanvas !== "undefined") {
    App.FabricHistory.init(fabricCanvas);
  }

  // Set up keyboard shortcuts
  document.addEventListener("keydown", function (e) {
    if (e.ctrlKey && e.key === "z" && !e.shiftKey) {
      e.preventDefault();
      App.FabricHistory.undo();
    } else if ((e.ctrlKey && e.key === "y") || (e.ctrlKey && e.shiftKey && e.key === "Z")) {
      e.preventDefault();
      App.FabricHistory.redo();
    }
  });

  // Initial button states
  if (typeof App.FabricHistory.updateButtonStates === "function") {
    App.FabricHistory.updateButtonStates();
  }
});

// دالة لتحديث الخط الخاص بكائن النص في لوحة fabric.js
function updateFabricTextFont(font) {
  if (fabricCanvas) {
    let activeObject = fabricCanvas.getActiveObject();
    // إذا كان هناك نص نشط بالفعل، نقوم بتحديث خاصية fontFamily
    if (activeObject && activeObject.type === "i-text") {
      activeObject.set({ fontFamily: font });
      fabricCanvas.renderAll();
    } else {
      // إذا لم يكن هناك نص نشط، نقوم بإنشاء كائن نص جديد في منتصف اللوحة
      const textObj = new fabric.IText(sampleText, {
        left: fabricCanvas.getWidth() * 0.85, // 85% من العرض
        top: fabricCanvas.getHeight() / 2, // 50% من الارتفاع
        fontFamily: font,
        fontSize: 40,
        originX: "center",
        originY: "center",
        fill: "black"
      });
      fabricCanvas.add(textObj);
      fabricCanvas.setActiveObject(textObj);
      fabricCanvas.renderAll();
    }
  }
}

// إضافة وظائف تغيير الخط عند التحويم والنقر على أزرار الخطوط
const fontButtons = document.querySelectorAll(".font-btn");
fontButtons.forEach((button) => {
  button.addEventListener("mouseover", function () {
    const font = this.getAttribute("data-font");
    updateFabricTextFont(font);
  });
  button.addEventListener("click", function () {
    const font = this.getAttribute("data-font");
    updateFabricTextFont(font);
  });
});

// إغلاق القوائم عند النقر خارجها

document.addEventListener("click", function (e) {
  const editorContainer = document.getElementById("image-editor-container");
  const bottomBar = document.getElementById("bottom-bar");
  const floatingMenu = document.getElementById("floating-menu");
  const stickersPanel = document.getElementById("stickers-panel");
  const fontButtonsContainer = document.getElementById("font-buttons-container");
  const filterButtonsContainer = document.getElementById("filter-buttons-container");
  const modelListOverlay = document.getElementById("model-list-overlay");
  if (
    modelListOverlay.style.display === "block" &&
    !document.getElementById("model-list-container").contains(e.target)
  ) {
    modelListOverlay.style.display = "none";
  }
  if (
    !editorContainer.contains(e.target) &&
    !bottomBar.contains(e.target) &&
    !floatingMenu.contains(e.target) &&
    !stickersPanel.contains(e.target) &&
    !fontButtonsContainer.contains(e.target) &&
    !filterButtonsContainer.contains(e.target)
  ) {
    editorContainer.style.display = "none";
    floatingMenu.style.display = "none";
    stickersPanel.style.display = "none";
    fontButtonsContainer.style.display = "none";
    filterButtonsContainer.style.display = "none";
  }
});

// منع انتشار الحدث داخل القوائم نفسها
document.querySelectorAll(".controls, .floating-menu, .panel, .sidebar").forEach((menu) => {
  menu.addEventListener("click", function (event) {
    event.stopPropagation();
  });
});
// زر "إضافة نصوص"
document.getElementById("add-text-btn").addEventListener("click", function (e) {
  e.stopPropagation();
  showFabricEditor();

  const textContent = "نص افتراضي";
  const fabricText = new fabric.IText(textContent, {
    fill: "#000",
    fontSize: 50
  });

  // أضف النص مؤقتًا لحساب أبعاده
  fabricCanvas.add(fabricText);

  // احسب المركز بناءً على حجم الـ canvas وحجم النص
  const canvasCenter = {
    x: fabricCanvas.getWidth() / 2,
    y: fabricCanvas.getHeight() / 2
  };

  fabricText.set({
    left: canvasCenter.x - fabricText.width / 2,
    top: canvasCenter.y - fabricText.height / 2
  });

  fabricCanvas.setActiveObject(fabricText);
  fabricCanvas.renderAll();
});

// زر "إضافة صور" (تحميل عدة // Function to handle the "add images" button click
document.getElementById("add-images-btn").addEventListener("click", function (e) {
  e.stopPropagation(); // Prevent default event propagation
  showFabricEditor(); // Show fabric editor (function assumed to be defined elsewhere)
  document.getElementById("upload-btn").click(); // Trigger file upload dialog
});

document.getElementById("upload-btn").addEventListener("change", function (e) {
  if (!fabricCanvas) return;

  const files = e.target.files;
  for (let i = 0; i < files.length; i++) {
    const reader = new FileReader();
    reader.onload = function (f) {
      const data = f.target.result;

      fabric.Image.fromURL(
        data,
        function (img) {
          const canvasWidth = fabricCanvas.getWidth();
          const canvasHeight = fabricCanvas.getHeight();

          const imgOriginalWidth = img.width;
          const imgOriginalHeight = img.height;

          // ✅ احسب نسبة التصغير للحفاظ على الأبعاد داخل القماش
          const scaleX = canvasWidth / imgOriginalWidth;
          const scaleY = canvasHeight / imgOriginalHeight;
          const scale = Math.min(scaleX, scaleY); // الحفاظ على النسبة

          // ✅ طبق التصغير بدقة
          img.set({
            scaleX: scale,
            scaleY: scale,
            left: (canvasWidth - imgOriginalWidth * scale) / 2,
            top: (canvasHeight - imgOriginalHeight * scale) / 2,
            selectable: true, // يمكنك تغييره حسب الحاجة
            hasControls: true,
            hasBorders: true
          });

          // ✅ أضف الصورة للقماش
          fabricCanvas.add(img);
          fabricCanvas.renderAll();
        },
        { crossOrigin: "anonymous" }
      ); // لتفادي مشاكل CORS عند رفع صور من الإنترنت
    };
    reader.readAsDataURL(files[i]);
  }
});

// زر "عرض القماشة"
document.getElementById("toggle-canvas-btn").addEventListener("click", function (e) {
  e.stopPropagation();
  const editorContainer = document.getElementById("image-editor-container");
  if (editorContainer.style.display === "block") {
    editorContainer.style.display = "none";
  } else {
    editorContainer.style.display = "block";
    initFabricCanvas();
  }
});
document.addEventListener("click", function (event) {
  const container = document.getElementById("image-editor-container");

  // إذا لم يكن الحاوية موجودة، لا تفعل شيء
  if (!container) return;

  // تحقق هل الضغط تم داخل الحاوية
  const isClickInside = container.contains(event.target);

  if (!isClickInside) {
    container.style.display = "none"; // إخفاء الحاوية
  }
});
// زر "ستيكرات"
document.getElementById("add-stickers-btn").addEventListener("click", function (e) {
  e.stopPropagation();
  const stickersPanel = document.getElementById("stickers-panel");
  stickersPanel.style.display = stickersPanel.style.display === "block" ? "none" : "block";
});

// عند النقر على أي صورة من الستكرات
document.querySelectorAll("#stickers-panel .sticker-item").forEach(function (sticker) {
  sticker.addEventListener("click", function (e) {
    e.stopPropagation();
    const stickerUrl = this.src;

    fabric.Image.fromURL(stickerUrl, function (img) {
      // ضبط مقياس الملصق
      img.scale(0.1);

      // ضبط نقطة الأصل إلى المركز
      img.set({
        originX: "center",
        originY: "center",
        left: fabricCanvas.getWidth() * 0.85, // 85% من العرض
        top: fabricCanvas.getHeight() / 2 // 50% من الارتفاع
      });

      fabricCanvas.add(img);
      fabricCanvas.renderAll();
    });
  });
});

// وظائف القائمة العائمة للنصوص
document.getElementById("btn-bring-front").addEventListener("click", function (e) {
  e.stopPropagation();
  const obj = fabricCanvas.getActiveObject();
  if (obj) {
    fabricCanvas.bringToFront(obj);
    fabricCanvas.renderAll();
  }
});
document.getElementById("btn-send-back").addEventListener("click", function (e) {
  e.stopPropagation();
  const obj = fabricCanvas.getActiveObject();
  if (obj) {
    fabricCanvas.sendToBack(obj);
    fabricCanvas.renderAll();
  }
});
document.getElementById("btn-copy").addEventListener("click", function (e) {
  e.stopPropagation();
  const obj = fabricCanvas.getActiveObject();
  if (obj) {
    obj.clone(function (cloned) {
      cloned.set({ left: obj.left + 20, top: obj.top + 20 });
      fabricCanvas.add(cloned);
      fabricCanvas.setActiveObject(cloned);
      fabricCanvas.renderAll();
    });
  }
});
document.getElementById("btn-delete").addEventListener("click", function (e) {
  e.stopPropagation();
  const obj = fabricCanvas.getActiveObject();
  if (obj) {
    fabricCanvas.remove(obj);
    fabricCanvas.renderAll();
  }
});

// Handle text color button click
document.getElementById("btn-text-color").addEventListener("click", function (e) {
  e.stopPropagation();
  const activeObject = fabricCanvas.getActiveObject();
  if (activeObject && activeObject.type === "i-text") {
    const picker = document.getElementById("color-picker");
    const pickerContainer = document.getElementById("color-picker");

    // Toggle picker visibility
    if (pickerContainer.style.display === "block") {
      pickerContainer.style.display = "none";
      return;
    }

    // Position the picker near the button
    const btnRect = this.getBoundingClientRect();
    pickerContainer.style.display = "block";
    pickerContainer.style.position = "absolute";
    pickerContainer.style.top = btnRect.bottom + window.scrollY + 5 + "px";
    pickerContainer.style.left = btnRect.left + window.scrollX + "px";

    // Set current color if exists
    if (activeObject.fill) {
      picker.value = activeObject.fill;
    }

    // Focus the color picker for better UX
    picker.focus();
  }
});

// Handle color picker changes
document.getElementById("color-picker").addEventListener("input", function (e) {
  const activeObject = fabricCanvas.getActiveObject();
  if (activeObject && activeObject.type === "i-text") {
    activeObject.set({
      fill: e.target.value,
      dirty: true
    });
    fabricCanvas.renderAll();
  }
});

// Close color picker when clicking outside
document.addEventListener("click", function (e) {
  const pickerContainer = document.getElementById("color-picker");
  const colorBtn = document.getElementById("btn-text-color");

  if (
    pickerContainer &&
    pickerContainer.style.display === "block" &&
    e.target !== colorBtn &&
    !colorBtn.contains(e.target) &&
    e.target !== pickerContainer &&
    !pickerContainer.contains(e.target)
  ) {
    pickerContainer.style.display = "none";
  }
});

document.getElementById("btn-font").addEventListener("click", function (e) {
  e.stopPropagation();
  const activeObject = fabricCanvas.getActiveObject();
  if (activeObject && activeObject.type === "i-text") {
    document.getElementById("font-buttons-container").style.display = "flex";
  }
});
document.querySelectorAll("#font-buttons-container .font-btn").forEach(function (btn) {
  btn.addEventListener("click", function (e) {
    e.stopPropagation();
    const font = this.getAttribute("data-font");
    const activeObject = fabricCanvas.getActiveObject();
    if (activeObject && activeObject.type === "i-text") {
      activeObject.set("fontFamily", font);
      fabricCanvas.renderAll();
    }
    document.getElementById("font-buttons-container").style.display = "none";
  });
});

// --- وظائف التحكم في النموذج (الجانب الأيمن) ---
document.getElementById("btn-model-swing").addEventListener("click", function () {
  swing = !swing;
});
document.getElementById("btn-model-hover").addEventListener("click", function () {
  hover = !hover;
});
document.getElementById("btn-model-jump").addEventListener("click", function () {
  jump = true;
  setTimeout(() => {
    jump = false;
  }, 500);
});
document.getElementById("btn-model-rotate").addEventListener("click", function (e) {
  e.stopPropagation();
  if (rotationSpeed === 0) {
    rotationSpeed = 0.01;
    this.classList.add("active");
    // إزالة حالة التنشيط من الأزرار الأخرى
    document.getElementById("btn-model-reverse").classList.remove("active");
    document.getElementById("btn-scene-rotate").classList.remove("active");
    // إيقاف دوران المشهد
    controls.autoRotate = false;
  } else {
    rotationSpeed = 0;
    this.classList.remove("active");
  }
});
document.getElementById("btn-model-reverse").addEventListener("click", function (e) {
  e.stopPropagation();
  if (rotationSpeed === 0) {
    rotationSpeed = -0.01;
    this.classList.add("active");
    // إزالة حالة التنشيط من الأزرار الأخرى
    document.getElementById("btn-model-rotate").classList.remove("active");
    document.getElementById("btn-scene-rotate").classList.remove("active");
    // إيقاف دوران المشهد
    controls.autoRotate = false;
  } else {
    rotationSpeed = 0;
    this.classList.remove("active");
  }
});
document.getElementById("btn-scene-rotate").addEventListener("click", function (e) {
  e.stopPropagation();
  if (!controls.autoRotate) {
    // تشغيل دوران المشهد
    controls.autoRotate = true;
    controls.autoRotateSpeed = 5;
    this.classList.add("active");
    // إزالة حالة التنشيط من الأزرار الأخرى وإيقاف دوران النموذج
    document.getElementById("btn-model-rotate").classList.remove("active");
    document.getElementById("btn-model-reverse").classList.remove("active");
    rotationSpeed = 0;
  } else {
    // إيقاف دوران المشهد
    controls.autoRotate = false;
    this.classList.remove("active");
  }
});

// =============================
// إضافة وظائف قائمة الفلاتر
// =============================
// 🧠 قائمة الفلاتر نفسها
const filterList = {
  Grayscale: new fabric.Image.filters.Grayscale(),
  Invert: new fabric.Image.filters.Invert(),
  Sepia: new fabric.Image.filters.Sepia(),
  Brightness: new fabric.Image.filters.Brightness({ brightness: 0.2 }),
  Contrast: new fabric.Image.filters.Contrast({ contrast: 0.3 }),
  Saturation: new fabric.Image.filters.Saturation({ saturation: 0.3 }),
  HueRotation: new fabric.Image.filters.HueRotation({ rotation: 1 }),
  Noise: new fabric.Image.filters.Noise({ noise: 200 }),
  Pixelate: new fabric.Image.filters.Pixelate({ blocksize: 10 }),
  Blur: new fabric.Image.filters.Blur({ blur: 0.5 }),
  Sharpen: new fabric.Image.filters.Convolute({
    matrix: [0, -1, 0, -1, 5, -1, 0, -1, 0]
  }),
  Emboss: new fabric.Image.filters.Convolute({
    matrix: [-2, -1, 0, -1, 1, 1, 0, 1, 2]
  }),
  Brownie: new fabric.Image.filters.Brownie(),
  Vintage: new fabric.Image.filters.Vintage(),
  Technicolor: new fabric.Image.filters.Technicolor(),
  Kodachrome: new fabric.Image.filters.Kodachrome(),
  Polaroid: new fabric.Image.filters.Polaroid(),
  RemoveColor: new fabric.Image.filters.RemoveColor({ color: "#00f" }),
  BlendColor: new fabric.Image.filters.BlendColor({
    color: "#f00",
    mode: "multiply"
  }),
  Gamma: new fabric.Image.filters.Gamma({ gamma: [0.9, 0.8, 0.8] })
};

// 🖼️ توليد واجهة مصغرات الفلاتر
(function initFilterSliderWithLabels() {
  const slider = document.getElementById("filterSlider");
  if (!slider) return;
  slider.innerHTML = "";

  Object.entries(filterList).forEach(([name, filter]) => {
    // الغلاف الخارجي
    const wrapper = document.createElement("div");
    wrapper.style.display = "flex";
    wrapper.style.flexDirection = "column";
    wrapper.style.alignItems = "center";
    wrapper.style.width = "80px";
    wrapper.style.textAlign = "center";

    // الصورة المصغرة
    const img = document.createElement("img");
    img.src = `img/filters/${name.toLowerCase()}.jpg`;
    img.alt = name;
    img.title = `تأثير: ${name}`;
    img.style.width = "64px";
    img.style.height = "64px";
    img.style.borderRadius = "8px";
    img.style.border = "2px solid transparent";
    img.style.cursor = "pointer";
    img.style.transition = "border-color 0.3s ease";

    img.addEventListener("mouseenter", () => {
      img.style.borderColor = "#d3a77b";
    });
    img.addEventListener("mouseleave", () => {
      img.style.borderColor = "transparent";
    });

    img.addEventListener("click", () => {
      const obj = fabricCanvas?.getActiveObject();
      if (!obj || obj.type !== "image") return;
      const freshFilter = filter.clone ? filter.clone() : new filter.constructor(filter);
      obj.filters = [freshFilter];
      obj.applyFilters();
      fabricCanvas.renderAll();
      saveState();
    });

    // اسم الفلتر
    const label = document.createElement("div");
    label.textContent = name;
    label.style.fontSize = "12px";
    label.style.color = "#d3a77b"; // اللون المطلوب
    label.style.marginTop = "6px";
    label.style.direction = "ltr";
    label.style.whiteSpace = "nowrap";

    wrapper.appendChild(img);
    wrapper.appendChild(label);
    slider.appendChild(wrapper);
  });
})();

// =============================
// إضافة وظائف القص Crop
// =============================
const startCrop = document.getElementById("startCrop");

// زر إظهار/إخفاء أشرطة الفلاتر والقص
// زر إظهار/إخفاء الشريط
const filterToggleBtn = document.getElementById("filterToggle");
if (filterToggleBtn) {
  filterToggleBtn.addEventListener("click", function (e) {
    e.stopPropagation();
    const slider = document.getElementById("filterSlider");
    if (!slider) return;
    slider.style.display = slider.style.display === "none" || slider.style.display === "" ? "flex" : "none";
  });
}

document.querySelectorAll("#filter-buttons-container button").forEach(function (btn) {
  btn.addEventListener("click", function (e) {
    e.stopPropagation();
    const filterType = this.getAttribute("data-filter");
    applyFilter(filterType);
  });
});

// فحص ما إذا كان المستخدم مشتركًا أم لا
function checkUserSubscription() {
  // التحقق من وجود معرف الاشتراك في الكوكيز أو الجلسة
  const subscriptionId = localStorage.getItem("subscription_id") || sessionStorage.getItem("subscription_id");

  // إذا كان هناك معرف اشتراك، فهذا يعني أن المستخدم مشترك
  if (subscriptionId && subscriptionId !== "" && !subscriptionId.startsWith("TEST-")) {
    return true;
  }

  // إذا كان المستخدم مسجل الدخول، يمكن التحقق من حالة الاشتراك من الخادم
  const userLoggedIn = localStorage.getItem("user_logged_in") === "true";
  if (userLoggedIn) {
    // التحقق من حالة الاشتراك من قاعدة البيانات باستخدام AJAX
    const userId = localStorage.getItem("user_id");
    if (userId) {
      // هنا يمكن إضافة طلب AJAX للتحقق من حالة الاشتراك من قاعدة البيانات
      // مثال:
      /*
    let isSubscribed = false;
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'check_subscription.php?user_id=' + userId, false); // طلب متزامن
    xhr.onload = function() {
      if (xhr.status === 200) {
        const response = JSON.parse(xhr.responseText);
        isSubscribed = response.subscribed;
      }
    };
    xhr.send();
    return isSubscribed;
    */

      // للتبسيط، نعتبر أن المستخدم المسجل مشترك
      return true;
    }
  }

  // المستخدم غير مشترك
  return false;
}

// تحديث خيارات التصدير بناءً على حالة الاشتراك
function updateExportOptions() {
  const isSubscribed = checkUserSubscription();
  const subscriberOnlyOptions = document.querySelectorAll(".subscriber-only");

  // إذا لم يكن المستخدم مشتركًا، قم بإضافة فئة hidden لإخفاء الخيارات
  subscriberOnlyOptions.forEach((option) => {
    if (!isSubscribed) {
      option.classList.add("hidden");
      // التأكد من عدم تحديد خيار الفيديو إذا كان مخفيًا
      if (option.selected) {
        // إذا كان خيار الفيديو محددًا، قم بتحديد خيار PNG بدلاً منه
        const exportFormat = document.getElementById("export-format");
        exportFormat.value = "png";
      }
    } else {
      option.classList.remove("hidden");
    }
  });

  // إذا كان المستخدم غير مشترك، إضافة نص "(للمشتركين فقط)" للخيارات المخفية
  if (!isSubscribed) {
    subscriberOnlyOptions.forEach((option) => {
      if (!option.textContent.includes("(للمشتركين فقط)")) {
        option.textContent += " (للمشتركين فقط)";
      }
    });
  }
}

// دالة إظهار/إخفاء قوائم التصدير السفلية
function toggleExportMenus() {
  const mediaMenu = document.getElementById("mediaMenu");
  const ccaptureMenu = document.getElementById("ccaptureMenu");
  // إذا كانت القوائم مخفية، قم بإظهارها؛ وإلا قم بإخفائها
  if (mediaMenu.style.display === "none" || mediaMenu.style.display === "") {
    mediaMenu.style.display = "flex";
    ccaptureMenu.style.display = "flex";
  } else {
    mediaMenu.style.display = "none";
    ccaptureMenu.style.display = "none";
  }
}

// وظيفة التصدير باستخدام MediaRecorder
let mediaRecorder;
let recordedChunks = [];
let isRecording = false;
let videoBlob = null;
let captureStream = null;
let recordingTimer = null;
let recordingTime = 0;

function exportMedia() {
  const format = document.getElementById("export-format").value;
  const dimensions = document.getElementById("export-dimensions").value.split("x");
  const width = parseInt(dimensions[0]);
  const height = parseInt(dimensions[1]);

  const originalSize = renderer.getSize(new THREE.Vector2());
  const originalAspect = camera.aspect;

  renderer.setSize(width, height);
  camera.aspect = width / height;
  camera.updateProjectionMatrix();

  // التحقق مما إذا كان المستخدم مشتركًا أم لا
  const isSubscribed = checkUserSubscription();

  if (format === "png" || format === "jpg") {
    // تصدير الصور متاح للجميع
    renderer.render(scene, camera);
    const dataURL = renderer.domElement.toDataURL("image/" + format);
    const link = document.createElement("a");
    link.href = dataURL;
    link.download = "exported." + format;
    link.click();
  } else if (format === "webm" || format === "mp4") {
    // التحقق من الاشتراك قبل السماح بتصدير الفيديو
    if (!isSubscribed) {
      // إذا لم يكن المستخدم مشتركًا، عرض رسالة وتوجيهه للاشتراك
      alert("تصدير الفيديو متاح فقط للمشتركين. يرجى الاشتراك للوصول إلى هذه الميزة.");

      // استعادة الأبعاد الأصلية
      renderer.setSize(originalSize.width, originalSize.height);
      camera.aspect = originalAspect;
      camera.updateProjectionMatrix();

      // سؤال المستخدم إذا كان يريد الاشتراك
      if (confirm("هل ترغب في الاشتراك الآن للوصول إلى تصدير الفيديو والمزيد من الميزات؟")) {
        showRegistrationModal();
      }
      return;
    }

    // المستخدم مشترك، إكمال عملية تصدير الفيديو
    let duration = prompt("أدخل مدة تسجيل الفيديو (بالثواني):", "5");
    duration = parseFloat(duration);
    if (isNaN(duration) || duration <= 0) {
      alert("يرجى إدخال مدة صحيحة.");
      renderer.setSize(originalSize.width, originalSize.height);
      camera.aspect = originalAspect;
      camera.updateProjectionMatrix();
      return;
    }

    const stream = renderer.domElement.captureStream(30);
    const recordedChunks = [];
    const mediaRecorder = new MediaRecorder(stream, {
      mimeType: "video/" + format
    });
    mediaRecorder.ondataavailable = (event) => {
      if (event.data.size > 0) recordedChunks.push(event.data);
    };
    mediaRecorder.onstop = () => {
      const blob = new Blob(recordedChunks, { type: "video/" + format });
      const link = document.createElement("a");
      link.href = URL.createObjectURL(blob);
      link.download = "exported." + format;
      link.click();
      renderer.setSize(originalSize.width, originalSize.height);
      camera.aspect = originalAspect;
      camera.updateProjectionMatrix();
    };
    mediaRecorder.start();
    setTimeout(() => mediaRecorder.stop(), duration * 1000);
  }

  // استعادة الأبعاد الأصلية بعد عملية التصدير
  renderer.setSize(originalSize.width, originalSize.height);
  camera.aspect = originalAspect;
  camera.updateProjectionMatrix();
}

// وظائف التسجيل باستخدام ccapture.js
function startRecordingCCapture() {
  const format = document.getElementById("ccapture-format").value;
  const dimensions = document.getElementById("ccapture-dimensions").value.split("x");
  const width = parseInt(dimensions[0]);
  const height = parseInt(dimensions[1]);

  const originalSize = renderer.getSize(new THREE.Vector2());
  const originalAspect = camera.aspect;

  renderer.setSize(width, height);
  camera.aspect = width / height;
  camera.updateProjectionMatrix();

  capturerCCapture = new CCapture({ format: format, framerate: 30 });
  capturerCCapture.start();

  let duration = prompt("أدخل مدة تسجيل الفيديو باستخدام ccapture (بالثواني):", "5");
  duration = parseFloat(duration);
  if (isNaN(duration) || duration <= 0) {
    alert("يرجى إدخال مدة صحيحة.");
    renderer.setSize(originalSize.width, originalSize.height);
    camera.aspect = originalAspect;
    camera.updateProjectionMatrix();
    capturerCCapture = null;
    return;
  }
  setTimeout(stopRecordingCCapture, duration * 1000);
}

function stopRecordingCCapture() {
  if (capturerCCapture) {
    capturerCCapture.stop();
    capturerCCapture.save();
    capturerCCapture = null;
    renderer.setSize(window.innerWidth, window.innerHeight);
    camera.aspect = window.innerWidth / window.innerHeight;
    camera.updateProjectionMatrix();
  }
}

// Initial call for managing controls visibility based on interactions
hideAllControls();

try {
  initScene();
} catch (error) {
  console.error("Error initializing Three.js scene:", error);
}

try {
  animateScene();
} catch (error) {
  console.error("Error animating Three.js scene:", error);
}
