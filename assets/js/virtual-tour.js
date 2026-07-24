/**
 * Virtual Tour 360° — JavaScript Controller
 * Sử dụng Pannellum.js
 * Thiết kế UX cho Người cao tuổi & Trẻ em
 */
(function () {
  'use strict';

  // ── State ──
  let viewer = null;
  let tourData = null;
  let currentSceneKey = null;
  let sceneKeys = [];
  let currentIndex = 0;
  let tourStartTime = null;
  let isAudioPlaying = false;
  let speechUtterance = null;

  const API_BASE = document.getElementById('vt-api-base')?.value || '';
  const DEST_ID = parseInt(document.getElementById('vt-dest-id')?.value || '0');

  // ── Init ──
  document.addEventListener('DOMContentLoaded', init);

  if ('speechSynthesis' in window) {
    // Pre-load voices to avoid empty getVoices() array on first click (common in Chrome)
    window.speechSynthesis.onvoiceschanged = () => window.speechSynthesis.getVoices();
  }

  async function init() {
    if (!DEST_ID) return;

    showLoading(true);

    try {
      const res = await fetch(`${API_BASE}/api/virtual_tour.php?destination_id=${DEST_ID}`);
      const data = await res.json();

      if (!data.success || data.total_scenes === 0) {
        showEmpty();
        return;
      }

      tourData = data;
      sceneKeys = Object.keys(tourData.scenes);
      currentSceneKey = tourData.default_scene || sceneKeys[0];
      currentIndex = sceneKeys.indexOf(currentSceneKey);

      initPannellum();
      renderSceneList();
      updateControls();
      logInteraction('view_tour');

      tourStartTime = Date.now();

      // Hiện onboarding cho lần đầu
      if (!localStorage.getItem('vt_onboarding_seen')) {
        showOnboarding();
      } else {
        showLoading(false);
      }

    } catch (err) {
      console.error('Lỗi tải Virtual Tour:', err);
      showEmpty();
    }
  }

  // ── Pannellum Init ──
  function initPannellum() {
    const scenesConfig = {};

    for (const [key, scene] of Object.entries(tourData.scenes)) {
      const hotSpots = scene.hotspots.map(hs => {
        const config = {
          pitch: hs.pitch,
          yaw: hs.yaw,
          type: hs.type === 'scene' ? 'scene' : 'info',
          text: hs.text || '',
          cssClass: hs.css_class || '',
        };

        if (hs.type === 'scene' && hs.target_scene_key) {
          config.sceneId = hs.target_scene_key;
        }

        if (hs.type === 'info') {
          config.clickHandlerFunc = function () {
            showInfoPopup(hs.text);
          };
        }

        return config;
      });

      scenesConfig[key] = {
        type: 'equirectangular',
        panorama: scene.panorama_url,
        title: scene.title,
        pitch: scene.pitch,
        yaw: scene.yaw,
        hfov: scene.hfov,
        minHfov: 50,
        maxHfov: 120,
        autoLoad: true,
        hotSpots: hotSpots,
      };
    }

    viewer = pannellum.viewer('vt-panorama', {
      default: {
        firstScene: currentSceneKey,
        autoLoad: true,
        compass: true,
        showControls: false,
        mouseZoom: false,       // Tắt zoom chuột → tránh người già cuộn nhầm
        keyboardZoom: false,
        draggable: true,
        orientationOnByDefault: false,
        crossOrigin: 'anonymous',
        sceneFadeDuration: 800,
      },
      scenes: scenesConfig,
    });

    // Listener: khi đổi cảnh
    viewer.on('scenechange', function (sceneId) {
      currentSceneKey = sceneId;
      currentIndex = sceneKeys.indexOf(sceneId);
      updateControls();
      updateSceneInfo();
      highlightActiveCard();
      logInteraction('change_scene', tourData.scenes[sceneId]?.id);
      stopAudio();
    });

    // Listener: khi load xong ảnh
    viewer.on('load', function () {
      showLoading(false);
      updateSceneInfo();
    });
  }

  // ── Navigation ──
  window.vtPrevScene = function () {
    if (currentIndex > 0) {
      const prevKey = sceneKeys[currentIndex - 1];
      viewer.loadScene(prevKey);
    }
  };

  window.vtNextScene = function () {
    if (currentIndex < sceneKeys.length - 1) {
      const nextKey = sceneKeys[currentIndex + 1];
      viewer.loadScene(nextKey);
    } else {
      // Tour hoàn tất
      logInteraction('complete_tour', null, Math.round((Date.now() - tourStartTime) / 1000));
    }
  };

  window.vtGoToScene = function (sceneKey) {
    if (viewer && tourData.scenes[sceneKey]) {
      viewer.loadScene(sceneKey);
    }
  };

  // ── Update UI ──
  function updateControls() {
    const prevBtn = document.getElementById('vt-prev-btn');
    const nextBtn = document.getElementById('vt-next-btn');
    const progressText = document.getElementById('vt-progress-text');
    const progressFill = document.getElementById('vt-progress-fill');
    const counter = document.getElementById('vt-scene-counter');

    if (prevBtn) prevBtn.disabled = currentIndex <= 0;
    if (nextBtn) nextBtn.disabled = currentIndex >= sceneKeys.length - 1;

    const current = currentIndex + 1;
    const total = sceneKeys.length;

    if (progressText) progressText.textContent = `${current}/${total}`;
    if (progressFill) progressFill.style.width = `${(current / total) * 100}%`;
    if (counter) counter.textContent = `📍 ${current} / ${total}`;
  }

  function updateSceneInfo() {
    const scene = tourData.scenes[currentSceneKey];
    if (!scene) return;

    const titleEl = document.getElementById('vt-scene-title');
    const descEl = document.getElementById('vt-scene-desc');

    if (titleEl) titleEl.textContent = scene.title;
    if (descEl) descEl.textContent = scene.description || '';
  }

  function highlightActiveCard() {
    document.querySelectorAll('.vt-scene-card').forEach(card => {
      card.classList.toggle('active', card.dataset.sceneKey === currentSceneKey);
    });
  }

  function renderSceneList() {
    const container = document.getElementById('vt-scenes-list');
    if (!container) return;

    container.innerHTML = '';

    for (const [key, scene] of Object.entries(tourData.scenes)) {
      const card = document.createElement('div');
      card.className = `vt-scene-card ${key === currentSceneKey ? 'active' : ''}`;
      card.dataset.sceneKey = key;
      card.onclick = () => vtGoToScene(key);

      const imgSrc = scene.thumbnail_url || scene.panorama_url;

      card.innerHTML = `
        <img class="vt-scene-card-img" src="${imgSrc}" alt="${scene.title}" loading="lazy"
             onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 160 90%22><rect fill=%22%231e293b%22 width=%22160%22 height=%2290%22/><text x=%2280%22 y=%2250%22 text-anchor=%22middle%22 fill=%22%2364748b%22 font-size=%2214%22>360°</text></svg>'">
        <div class="vt-scene-card-title">${scene.title}</div>
      `;

      container.appendChild(card);
    }
  }

  // ── Audio / Text-to-Speech ──
  window.vtToggleAudio = function () {
    const scene = tourData.scenes[currentSceneKey];
    if (!scene) return;

    const btn = document.getElementById('vt-audio-btn');

    if (isAudioPlaying) {
      stopAudio();
      return;
    }

    // Lấy ngôn ngữ và văn bản
    const langSelect = document.getElementById('vt-audio-lang');
    const selectedLang = langSelect ? langSelect.value : 'vi';
    
    let text = '';
    if (selectedLang === 'en') {
      text = scene.orig_desc_en || scene.orig_title_en;
    } else {
      text = scene.orig_desc_vi || scene.orig_title_vi;
    }

    if (text) {
      stopAudio();
      
      const targetLang = selectedLang === 'en' ? 'en' : 'vi';
      const ttsUrl = `/travel_daklak/api/tts.php?lang=${targetLang}&text=${encodeURIComponent(text)}`;
      
      let audio = new Audio(ttsUrl);
      
      audio.onended = function () {
        isAudioPlaying = false;
        if (btn) {
          btn.classList.remove('playing');
          btn.innerHTML = '🔊 ' + (btn.dataset.labelPlay || 'Nghe thuyết minh');
        }
      };

      audio.onerror = function() {
        console.error("Google TTS error. Using fallback TTS.");
        // Fallback to browser TTS if Google TTS fails (e.g. text too long)
        fallbackBrowserTTS(text, targetLang, btn);
      };

      audio.play().then(() => {
        isAudioPlaying = true;
        // Gắn đối tượng audio này vào window để hàm stopAudio có thể tắt nó
        window.currentTtsAudio = audio;
        
        if (btn) {
          btn.classList.add('playing');
          btn.innerHTML = '⏹ ' + (btn.dataset.labelStop || 'Dừng phát');
        }
      }).catch(err => {
        console.error("Audio play blocked:", err);
        fallbackBrowserTTS(text, targetLang, btn);
      });
    }
  };

  function fallbackBrowserTTS(text, lang, btn) {
    if (!('speechSynthesis' in window)) return;
    
    let speechUtterance = new SpeechSynthesisUtterance(text);
    speechUtterance.lang = lang === 'en' ? 'en-US' : 'vi-VN';
    speechUtterance.rate = 0.9;
    
    const voices = window.speechSynthesis.getVoices();
    const voice = voices.find(v => v.lang.toLowerCase().includes(lang));
    if (voice) speechUtterance.voice = voice;

    speechUtterance.onend = function () {
      isAudioPlaying = false;
      if (btn) {
        btn.classList.remove('playing');
        btn.innerHTML = '🔊 ' + (btn.dataset.labelPlay || 'Nghe thuyết minh');
      }
    };

    window.speechSynthesis.speak(speechUtterance);
    isAudioPlaying = true;
    if (btn) {
      btn.classList.add('playing');
      btn.innerHTML = '⏹ ' + (btn.dataset.labelStop || 'Dừng phát');
    }
  }

  function stopAudio() {
    if (window.currentTtsAudio) {
      window.currentTtsAudio.pause();
      window.currentTtsAudio.currentTime = 0;
      window.currentTtsAudio = null;
    }
    if ('speechSynthesis' in window) {
      window.speechSynthesis.cancel();
    }
    isAudioPlaying = false;
    const btn = document.getElementById('vt-audio-btn');
    if (btn) {
      btn.classList.remove('playing');
      btn.innerHTML = '🔊 ' + (btn.dataset.labelPlay || 'Nghe thuyết minh');
    }
  }

  // ── Accessibility: Font size ──
  window.vtFontIncrease = function () {
    const panel = document.querySelector('.vt-scene-info-panel');
    if (panel) {
      const current = parseFloat(getComputedStyle(panel).fontSize);
      panel.style.fontSize = Math.min(current + 2, 28) + 'px';
    }
  };

  window.vtFontDecrease = function () {
    const el = document.querySelector('.vt-scene-info-panel');
    if (!el) return;
    let size = parseInt(window.getComputedStyle(el).fontSize);
    el.style.fontSize = Math.max(14, size - 2) + 'px';
  };

  window.vtChangeAudioLang = function () {
    if (isAudioPlaying) {
      // Nếu đang phát thì dừng lại và phát lại bằng ngôn ngữ mới
      stopAudio();
      window.vtToggleAudio();
    }
  };

  // ── Fullscreen ──
  window.vtToggleFullscreen = function () {
    const wrapper = document.querySelector('.vt-viewer-wrapper');
    if (!wrapper) return;

    wrapper.classList.toggle('fullscreen');
    const isFS = wrapper.classList.contains('fullscreen');

    const btn = document.getElementById('vt-fullscreen-btn');
    if (btn) btn.textContent = isFS ? '✕' : '⛶';

    // Resize pannellum
    if (viewer) {
      setTimeout(() => viewer.resize(), 100);
    }
  };

  // ── Onboarding ──
  function showOnboarding() {
    const overlay = document.getElementById('vt-onboarding');
    if (overlay) {
      overlay.classList.remove('hidden');
      showLoading(false);
    }
  }

  window.vtDismissOnboarding = function () {
    const overlay = document.getElementById('vt-onboarding');
    if (overlay) overlay.classList.add('hidden');
    localStorage.setItem('vt_onboarding_seen', '1');
  };

  // ── Info Popup (cho hotspot info) ──
  function showInfoPopup(text) {
    // Pannellum xử lý popup mặc định, chỉ log
    logInteraction('click_hotspot');
  }

  // ── Loading ──
  function showLoading(show) {
    const el = document.getElementById('vt-loading');
    if (el) el.classList.toggle('hidden', !show);
  }

  function showEmpty() {
    showLoading(false);
    const el = document.getElementById('vt-empty');
    if (el) el.style.display = 'block';
  }

  // ── Analytics ──
  function logInteraction(action, sceneId, duration) {
    if (!DEST_ID) return;

    const fd = new FormData();
    fd.append('destination_id', DEST_ID);
    fd.append('log_action', action);
    if (sceneId) fd.append('scene_id', sceneId);
    if (duration) fd.append('duration', duration);

    fetch(`${API_BASE}/api/virtual_tour.php?action=log`, {
      method: 'POST',
      body: fd,
    }).catch(() => {});
  }

  // Log tour duration khi rời trang
  window.addEventListener('beforeunload', function () {
    if (tourStartTime) {
      const duration = Math.round((Date.now() - tourStartTime) / 1000);
      if (duration > 3) {
        const fd = new FormData();
        fd.append('destination_id', DEST_ID);
        fd.append('log_action', 'view_tour');
        fd.append('duration', duration);
        navigator.sendBeacon(`${API_BASE}/api/virtual_tour.php?action=log`, fd);
      }
    }
  });

})();
