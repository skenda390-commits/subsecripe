// assets/js/scene.js

document.addEventListener('DOMContentLoaded', () => {
    // Check if we are on the main page
    if (!document.getElementById('scene-container')) return;

    let scene, camera, renderer, model, controls, capturer;
    let isRecording = false;

    const container = document.getElementById('scene-container');
    const exportFormatSelect = document.getElementById('export-format');
    const dimensionsSelect = document.getElementById('export-dimensions');
    const durationInput = document.getElementById('video-duration');
    const durationContainer = document.getElementById('video-duration-container');
    const captureButton = document.getElementById('capture-button');
    const notification = document.getElementById('export-notification');

    function init() {
        // Scene
        scene = new THREE.Scene();
        scene.background = new THREE.Color(0x1e1e1e); // Dark background

        // Camera
        camera = new THREE.PerspectiveCamera(75, container.clientWidth / container.clientHeight, 0.1, 1000);
        camera.position.z = 5;

        // Renderer
        renderer = new THREE.WebGLRenderer({ antialias: true, preserveDrawingBuffer: true });
        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setPixelRatio(window.devicePixelRatio);
        renderer.outputEncoding = THREE.sRGBEncoding;
        container.appendChild(renderer.domElement);

        // Controls
        controls = new THREE.OrbitControls(camera, renderer.domElement);
        controls.enableDamping = true;

        // Lighting
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.5);
        scene.add(ambientLight);
        const directionalLight = new THREE.DirectionalLight(0xffffff, 1);
        directionalLight.position.set(5, 5, 5);
        scene.add(directionalLight);

        // Load Model
        const loader = new THREE.GLTFLoader();
        // A placeholder path, assuming a model exists.
        loader.load('../models/scene.gltf', (gltf) => {
            model = gltf.scene;
            scene.add(model);
            animate();
        }, undefined, (error) => {
            console.error('An error happened while loading the model:', error);
            // Display error in the container
            container.innerHTML = '<p style="color: red; text-align: center;">Failed to load 3D model. Please place a model at `models/scene.gltf`</p>';
        });

        // Handle window resizing
        window.addEventListener('resize', onWindowResize, false);

        // Setup event listeners for export
        captureButton.addEventListener('click', handleCapture);
        exportFormatSelect.addEventListener('change', toggleVideoDuration);
        toggleVideoDuration();
    }

    function onWindowResize() {
        camera.aspect = container.clientWidth / container.clientHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(container.clientWidth, container.clientHeight);
    }

    function animate() {
        requestAnimationFrame(animate);
        controls.update();
        if (model) {
            // Example animation: slowly rotate the model
            model.rotation.y += 0.005;
        }
        renderer.render(scene, camera);

        if (isRecording && capturer) {
            capturer.capture(renderer.domElement);
        }
    }

    function toggleVideoDuration() {
        const format = exportFormatSelect.value;
        if (format === 'webm' || format === 'mp4') {
            durationContainer.style.display = 'block';
        } else {
            durationContainer.style.display = 'none';
        }
    }

    async function handleCapture() {
        // First, check user status
        if (!window.userData) {
            showNotification('Please log in to export.', true);
            // Optional: open login modal
            document.getElementById('user-btn').click();
            return;
        }

        const format = exportFormatSelect.value;
        const isVideo = format === 'webm' || format === 'mp4';

        const usage = window.userData.usage;
        const sub = window.userData.subscription;

        // Check subscription for video
        if (isVideo && sub.id === 1) { // Free plan
             showNotification('Video export is a premium feature. Please subscribe.', true);
             // Optional: open plans tab
             document.getElementById('user-btn').click();
             setTimeout(() => openTab(null, 'plans-tab'), 100);
             return;
        }

        // Check usage limits
        if (isVideo && usage.videosRemaining <= 0) {
            showNotification('You have no video credits left for this month.', true);
            return;
        }
        if (!isVideo && usage.imagesRemaining <= 0) {
            showNotification('You have no image credits left for this month.', true);
            return;
        }

        // Proceed with capture
        if (isVideo) {
            startRecording(format);
        } else {
            captureImage(format);
        }
    }

    function captureImage(format) {
        showNotification(`Exporting ${format.toUpperCase()}...`);
        const [width, height] = dimensionsSelect.value.split('x').map(Number);

        // Resize for capture
        renderer.setSize(width, height);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        renderer.render(scene, camera);

        const dataURL = renderer.domElement.toDataURL(`image/${format}`);

        // Restore original size
        onWindowResize();

        // Trigger download
        const link = document.createElement('a');
        link.download = `capture.${format}`;
        link.href = dataURL;
        link.click();

        showNotification('Image exported successfully!', false);
        updateUsage('image');
    }

    function startRecording(format) {
        isRecording = true;
        const [width, height] = dimensionsSelect.value.split('x').map(Number);
        const duration = parseInt(durationInput.value);
        const framerate = 30;

        capturer = new CCapture({
            format: format,
            framerate: framerate,
            name: 'capture',
            quality: 90,
            verbose: false,
            width: width,
            height: height
        });

        // Resize for capture
        renderer.setSize(width, height);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();

        capturer.start();
        showNotification(`Recording ${format.toUpperCase()} for ${duration} seconds...`);

        setTimeout(() => {
            stopRecording();
        }, duration * 1000);
    }

    function stopRecording() {
        isRecording = false;
        capturer.stop();
        capturer.save();

        // Restore original size
        onWindowResize();

        showNotification('Video exported successfully!', false);
        updateUsage('video');
    }

    async function updateUsage(type) {
        try {
            const response = await fetch('../api/update_usage.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type: type }) // 'image' or 'video'
            });
            const result = await response.json();
            if (result.status === 'success') {
                // Update the global user data object
                window.userData.usage = result.newUsage;
                console.log('Usage updated successfully.');
            } else {
                console.error('Failed to update usage:', result.message);
                showNotification(`Error updating usage: ${result.message}`, true);
            }
        } catch (error) {
            console.error('Error calling update_usage.php:', error);
        }
    }

    function showNotification(message, isError = false) {
        notification.textContent = message;
        notification.style.backgroundColor = isError ? '#f44336' : '#4caf50';
        notification.classList.add('show');

        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    // Initialize the scene
    init();
});