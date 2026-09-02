<?php
session_start();


?>
<!DOCTYPE>
<html>
<head>
<title>ThunderBible</title>
<link rel="stylesheet" href="styles.css?cache=<?= md5_file('styles.css'); ?>" type="text/css">
</head>
<body>
<div id="wrapper" class="gold-border">

</div>

<div id="addNewPanel">+</div>


<script>
let bibles;
let mainPanelId;
let lexiconPanelId;
const BibleBookMap = {
    GEN: "genesis",
    EXO: "exodus",
    LEV: "leviticus",
    NUM: "numbers",
    DEU: "deuteronomy",

    JOS: "joshua",
    JDG: "judges",
    RUT: "ruth",
    "1SA": "1_samuel",
    "2SA": "2_samuel",
    "1KI": "1_kings",
    "2KI": "2_kings",
    "1CH": "1_chronicles",
    "2CH": "2_chronicles",
    EZR: "ezra",
    NEH: "nehemiah",
    EST: "esther",
    JOB: "job",
    PSA: "psalms",
    PRO: "proverbs",
    ECC: "ecclesiastes",
    SNG: "song-of-solomon",

    ISA: "isaiah",
    JER: "jeremiah",
    LAM: "lamentations",
    EZK: "ezekiel",
    DAN: "daniel",
    HOS: "hosea",
    JOL: "joel",
    AMO: "amos",
    OBA: "obadiah",
    JON: "jonah",
    MIC: "micah",
    NAM: "nahum",
    HAB: "habakkuk",
    ZEP: "zephaniah",
    HAG: "haggai",
    ZEC: "zechariah",
    MAL: "malachi",

    MAT: "matthew",
    MRK: "mark",
    LUK: "luke",
    JHN: "john",
    ACT: "acts",
    ROM: "romans",
    "1CO": "1_corinthians",
    "2CO": "2_corinthians",
    GAL: "galatians",
    EPH: "ephesians",
    PHP: "philippians",
    COL: "colossians",
    "1TH": "1_thessalonians",
    "2TH": "2_thessalonians",
    "1TI": "1_timothy",
    "2TI": "2_timothy",
    TIT: "titus",
    PHM: "philemon",
    HEB: "hebrews",
    JAS: "james",
    "1PE": "1_peter",
    "2PE": "2_peter",
    "1JN": "1_john",
    "2JN": "2_john",
    "3JN": "3_john",
    JUD: "jude",
    REV: "revelation"
};
const allowedLangs = ["eng", "ita", "spa"];



/** INIT starts here! **/
document.addEventListener("DOMContentLoaded", async () => {
    await loadBibles();

    const saved = loadState();
    if (saved && Array.isArray(saved.panels) && saved.panels.length) {
        await restoreState(saved);
    } else {
        await loadDefaultLayout();
    }
});

async function loadDefaultLayout() {
    let savedInfo = {
        bibleId: '06125adad2d5898a-01',
        bookId: 'GEN',
        chapterId: 'GEN.5'
    }

    // left panel
    mainPanelId = createPanel();
    setBible(mainPanelId);
    await loadBooks(mainPanelId, savedInfo.bookId);
    await loadChapters(mainPanelId, savedInfo.chapterId);
    await loadVerses(mainPanelId);

    // right panel (Strong's lexicon)
    await makeLexiconPanel(BibleBookMap[savedInfo.bookId], '1', '5');
}

async function restoreState(state) {
    for (const p of state.panels) {
        if (p.type === 'lexicon') {
            await makeLexiconPanel(p.book, p.chapter, p.verse);
        } else {
            const panelId = createPanel();
            if (!mainPanelId) mainPanelId = panelId;
            setBible(panelId, p.bibleId);
            await loadBooks(panelId, p.bookId);
            await loadChapters(panelId, p.chapterId);
            await loadVerses(panelId);
        }
    }
}

async function makeLexiconPanel(book, chapter, verse) {
    lexiconPanelId = createPanel();
    const lexiconPanel = document.querySelector('#' + lexiconPanelId);
    lexiconPanel.querySelector('.bibleSelector').remove();
    lexiconPanel.querySelector('.bookSelector').remove();
    lexiconPanel.querySelector('.chapterSelector').remove();
    lexiconPanel.querySelector('.panelNav').remove();
    lexiconPanel.querySelector('.panelNav').remove();
    lexiconPanel.querySelector('.historyBack')?.remove();
    lexiconPanel.querySelector('.historyForward')?.remove();
    lexiconPanel.querySelector('.panelMenuDivider')?.remove();
    await loadLexicon(lexiconPanelId, book, chapter, verse);
}


async function loadBibles() {
    try {
        const res = await fetch('api.php?cmd=getBibles');

        if (!res.ok) {
            throw new Error('Network error: ' + res.status);
        }

        const json = await res.json();
        bibles = json.data;   // save globally

        const template = document.getElementById('biblePanelTemplate');
        const bibleSelect = template.content.querySelector('.bibleSelector');

        bibles = bibles.filter(b =>
            allowedLangs.includes(b.language.id.toLowerCase())
        );

        bibles.sort((a, b) => a.name.localeCompare(b.name));

        bibles.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.id;
            opt.textContent = `${b.abbreviation} ${b.name} ${b.description}`;
            bibleSelect.appendChild(opt);
        });
    }
    catch (err) {
        console.error("Error loading bibles:", err);
    }
}
function createPanel() {
    let template = document.querySelector('#biblePanelTemplate');
    let html = template.innerHTML;
    let panelId = 'panel' + crypto.randomUUID();
    html = html.replace(/{{biblePanelId}}/, panelId);
    document.querySelector('#wrapper').insertAdjacentHTML("beforeend", html)
    initPanelHistory(document.querySelector('#' + panelId));
    updateLayout();
    return panelId;
}

function setBible(panelId, bibleId='06125adad2d5898a-01') {
    let panel = document.querySelector('#' + panelId);
    panel.querySelector('.bibleSelector').value=bibleId;
}

async function loadBooks(panelId, bookId='') {

    let panel = document.querySelector('#' + panelId);
    let bibleId = panel.querySelector('.bibleSelector').value;
    let bookSelector = panel.querySelector('.bookSelector');
    let books;
    if (bibleId !== '') {
        try {
            const url = 'api.php?cmd=getBooks&bibleId=' + bibleId;
            console.log('Loading book: ' + url);
            const res = await fetch(url);
            if (!res.ok) {
                throw new Error('Network error: ' + res.status);
            }
            const json = await res.json();
            books = json.data;

            books.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.id;
                opt.textContent = `${b.name}`;
                bookSelector.appendChild(opt);
            });

            if (bookId !== '') {
                bookSelector.value=bookId;
            }

        }
        catch (err) {
            console.error("Error loading books:", err);
        }
    }
}

async function loadChapters(panelId, chapterId='') {
    let panel = document.querySelector('#' + panelId);
    let bibleId = panel.querySelector('.bibleSelector').value;
    let bookId = panel.querySelector('.bookSelector').value;
    let chapterSelector = panel.querySelector('.chapterSelector');
    let chapters;
    chapterSelector.innerHTML = '';
    try {
        const url = 'api.php?cmd=getChapters&bibleId=' + bibleId + '&bookId=' + bookId;
        console.log('Loading chapter: ' + url);
        const res = await fetch(url);
        if (!res.ok) {
            throw new Error('Network error: ' + res.status);
        }
        const json = await res.json();
        chapters = json.data;
        chapters.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = `${c.number}`;
            chapterSelector.appendChild(opt);
        })

        if (chapterId !== '') {
            chapterSelector.value=chapterId;
        }
    }
    catch (err) {
        console.error("Error loading books:", err);
    }
}

async function loadVerses(panelId, verseId='') {
    let panel = document.querySelector('#' + panelId);
    let bibleId = panel.querySelector('.bibleSelector').value;
    let chapterId = panel.querySelector('.chapterSelector').value;
    let panelContent = panel.querySelector('.biblePanelWindow');
    let verses;
    try {
        const url = 'api.php?cmd=getVerses&bibleId=' + bibleId + '&chapterId=' + chapterId;
        console.log('Loading verses: ' + url);
        const res = await fetch(url);
        if (!res.ok) {
            throw new Error('Network error: ' + res.status);
        }
        const json = await res.json();
        verses = json.data.content;
        panelContent.innerHTML = verses;
        wrapVerses(panelContent);
        rememberPanelLocation(panel);
        updatePanelMenuHistory(panel);
        saveState();
    }
    catch (err) {
        console.error("Error loading books:", err);
    }
}

async function loadLexicon(panelId, book, chapter, verse) {
    panel = document.querySelector('#' + lexiconPanelId);
    if (!panel) return;
    let panelContent = panel.querySelector('.biblePanelWindow');
    panel.dataset.book = book;
    panel.dataset.chapter = chapter;
    panel.dataset.verse = verse;
    try {
        const res = await fetch('api.php?cmd=getBibleHub&book=' + book + '&chapter=' + chapter + '&verse=' + verse);
        if (!res.ok) {
            throw new Error('Network error: ' + res.status);
        }
        panelContent.innerHTML = await res.text();
        fixLexiconLinks(panelContent);
        saveState();
    }
    catch (err) {
        console.error("Error loading books:", err);
    }
}



on(document, 'change', '.bibleSelector', async (e, el) => {
    const panel = closest(el, '.biblePanel');
    const panelId = panel.id;
    resetPanelHistory(panel);
    const bibleId = el.value;
    setBible(panelId, bibleId);
    await loadBooks(panelId);
    await loadChapters(panelId);
    await loadVerses(panelId);
});

on(document, 'change', '.bookSelector', async (e, el) => {
    const panel = closest(el, '.biblePanel');
    const panelId = panel.id;
    pushPanelHistory(panel);
    panel.querySelector('.chapterSelector').value=1;
    await loadChapters(panelId);
    await loadVerses(panelId);
    saveState();
});

on(document, 'change', '.chapterSelector', async (e, el) => {
    const panel = closest(el, '.biblePanel');
    const panelId = panel.id;
    pushPanelHistory(panel);
    await loadVerses(panelId);
});

on(document, 'click', '.verse-span', async (e, el) => {
    const panel = closest(el, '.biblePanel');
    const bookId = panel.querySelector('.bookSelector').value;
    const chapter = panel.querySelector('.chapterSelector');
    const chapterNumber = chapter.options[chapter.selectedIndex].text;
    const verse = el.dataset.verse;

    panel.querySelectorAll('.verse-span.verse-active').forEach(s => s.classList.remove('verse-active'));
    panel.querySelectorAll('.verse-span[data-verse="' + verse + '"]').forEach(s => s.classList.add('verse-active'));

    await loadLexicon(lexiconPanelId, BibleBookMap[bookId], chapterNumber, verse);
})

on(document, 'click', '.panelLeft', async (e, el) => {
    const panel = closest(el, '.biblePanel');
    const panelId = panel.id;
    pushPanelHistory(panel);
    const chapter = panel.querySelector('.chapterSelector');
    selectPrev(chapter);
    await loadVerses(panelId);
})

on(document, 'click', '.panelRight', async (e, el) => {
    const panel = closest(el, '.biblePanel');
    const panelId = panel.id;
    pushPanelHistory(panel);
    const chapter = panel.querySelector('.chapterSelector');
    selectNext(chapter);
    await loadVerses(panelId);
})

on(document, 'click', '#addNewPanel', async (e, el) => {
    const panelId = createPanel();
    setBible(panelId);
    await loadBooks(panelId, 'GEN');
    await loadChapters(panelId, 'GEN.1');
    await loadVerses(panelId);
})

on(document, 'click', '.bibleClose', (e, el) => {
    const panel = closest(el, '.biblePanel');
    panel.remove();
    updateLayout();
    saveState();
})

on(document, 'click', '.panelMenuButton', (e, el) => {
    e.stopPropagation();
    const menu = closest(el, '.panelMenu');
    const wasOpen = menu.classList.contains('open');
    closeAllPanelMenus();
    if (!wasOpen) {
        menu.classList.add('open');
        updatePanelMenuHistory(closest(el, '.biblePanel'));
    }
})

on(document, 'click', '.historyBack', async (e, el) => {
    e.stopPropagation();
    if (el.disabled) return;
    await panelHistoryBack(closest(el, '.biblePanel'));
    closeAllPanelMenus();
})

on(document, 'click', '.historyForward', async (e, el) => {
    e.stopPropagation();
    if (el.disabled) return;
    await panelHistoryForward(closest(el, '.biblePanel'));
    closeAllPanelMenus();
})

on(document, 'click', '.zoomIn', (e, el) => {
    e.stopPropagation();
    adjustZoom(closest(el, '.biblePanel'), 0.1);
    closeAllPanelMenus();
})

on(document, 'click', '.zoomOut', (e, el) => {
    e.stopPropagation();
    adjustZoom(closest(el, '.biblePanel'), -0.1);
    closeAllPanelMenus();
})

document.addEventListener('click', (e) => {
    if (e.target.closest('.panelMenu')) return;
    closeAllPanelMenus();
});

/** helper functions **/
function adjustZoom(panel, delta) {
    if (!panel) return;
    const win = panel.querySelector('.biblePanelWindow');
    let z = parseFloat(win.dataset.zoom || '1');
    z = Math.min(3, Math.max(0.5, z + delta));
    win.dataset.zoom = z;
    win.style.zoom = z;
}

function closeAllPanelMenus() {
    document.querySelectorAll('.panelMenu.open').forEach(menu => menu.classList.remove('open'));
}

function initPanelHistory(panel) {
    panel.dataset.historyBack = '[]';
    panel.dataset.historyForward = '[]';
    delete panel.dataset.lastBookId;
    delete panel.dataset.lastChapterId;
    delete panel.dataset.navigatingHistory;
}

function resetPanelHistory(panel) {
    initPanelHistory(panel);
    updatePanelMenuHistory(panel);
}

function getHistoryBack(panel) {
    try {
        return JSON.parse(panel.dataset.historyBack || '[]');
    } catch (err) {
        return [];
    }
}

function getHistoryForward(panel) {
    try {
        return JSON.parse(panel.dataset.historyForward || '[]');
    } catch (err) {
        return [];
    }
}

function setPanelHistory(panel, back, forward) {
    panel.dataset.historyBack = JSON.stringify(back);
    panel.dataset.historyForward = JSON.stringify(forward);
}

function rememberPanelLocation(panel) {
    const bookSel = panel.querySelector('.bookSelector');
    const chapterSel = panel.querySelector('.chapterSelector');
    if (!bookSel || !chapterSel) return;

    const bookId = bookSel.value;
    const chapterId = chapterSel.value;
    if (!bookId || !chapterId) return;

    panel.dataset.lastBookId = bookId;
    panel.dataset.lastChapterId = chapterId;
}

function pushPanelHistory(panel) {
    if (panel.dataset.navigatingHistory === '1') return;
    if (!panel.querySelector('.bookSelector')) return;

    const bookId = panel.dataset.lastBookId;
    const chapterId = panel.dataset.lastChapterId;
    if (!bookId || !chapterId) return;

    const back = getHistoryBack(panel);
    const last = back[back.length - 1];
    if (last && last.bookId === bookId && last.chapterId === chapterId) return;

    back.push({ bookId, chapterId });
    setPanelHistory(panel, back, []);
    updatePanelMenuHistory(panel);
}

function updatePanelMenuHistory(panel) {
    if (!panel) return;
    const backBtn = panel.querySelector('.historyBack');
    const forwardBtn = panel.querySelector('.historyForward');
    if (!backBtn || !forwardBtn) return;

    backBtn.disabled = getHistoryBack(panel).length === 0;
    forwardBtn.disabled = getHistoryForward(panel).length === 0;
}

async function goToPanelLocation(panel, { bookId, chapterId }) {
    const panelId = panel.id;
    const bookSelector = panel.querySelector('.bookSelector');

    if (bookSelector.value !== bookId) {
        bookSelector.value = bookId;
        await loadChapters(panelId, chapterId);
    } else {
        panel.querySelector('.chapterSelector').value = chapterId;
    }
    await loadVerses(panelId);
}

async function panelHistoryBack(panel) {
    const back = getHistoryBack(panel);
    if (!back.length) return;

    const current = {
        bookId: panel.dataset.lastBookId,
        chapterId: panel.dataset.lastChapterId
    };
    const forward = getHistoryForward(panel);
    if (current.bookId && current.chapterId) {
        forward.push(current);
    }

    const dest = back.pop();
    setPanelHistory(panel, back, forward);

    panel.dataset.navigatingHistory = '1';
    await goToPanelLocation(panel, dest);
    delete panel.dataset.navigatingHistory;
    updatePanelMenuHistory(panel);
}

async function panelHistoryForward(panel) {
    const forward = getHistoryForward(panel);
    if (!forward.length) return;

    const current = {
        bookId: panel.dataset.lastBookId,
        chapterId: panel.dataset.lastChapterId
    };
    const back = getHistoryBack(panel);
    if (current.bookId && current.chapterId) {
        back.push(current);
    }

    const dest = forward.pop();
    setPanelHistory(panel, back, forward);

    panel.dataset.navigatingHistory = '1';
    await goToPanelLocation(panel, dest);
    delete panel.dataset.navigatingHistory;
    updatePanelMenuHistory(panel);
}

function fixLexiconLinks(container) {
    container.querySelectorAll('a').forEach(a => {
        const href = a.getAttribute('href');
        if (href && !href.startsWith('#') && !/^(mailto|javascript):/i.test(href)) {
            // Resolve relative biblehub links against the biblehub domain.
            a.href = new URL(href, 'https://biblehub.com').href;
        }
        a.target = '_blank';
        a.rel = 'noopener';
    });
}

const STATE_KEY = 'thunderBibleState';

function saveState() {
    const wrapper = document.getElementById('wrapper');
    const panels = wrapper.querySelectorAll('.biblePanel');
    const state = { panels: [] };

    panels.forEach(panel => {
        const bibleSel = panel.querySelector('.bibleSelector');
        if (bibleSel) {
            state.panels.push({
                type: 'bible',
                bibleId: bibleSel.value,
                bookId: panel.querySelector('.bookSelector').value,
                chapterId: panel.querySelector('.chapterSelector').value
            });
        } else {
            // lexicon panel (its selectors were removed)
            state.panels.push({
                type: 'lexicon',
                book: panel.dataset.book || '',
                chapter: panel.dataset.chapter || '',
                verse: panel.dataset.verse || ''
            });
        }
    });

    try {
        localStorage.setItem(STATE_KEY, JSON.stringify(state));
    } catch (err) {
        console.error('Error saving state:', err);
    }
}

function loadState() {
    try {
        const raw = localStorage.getItem(STATE_KEY);
        return raw ? JSON.parse(raw) : null;
    } catch (err) {
        console.error('Error reading saved state:', err);
        return null;
    }
}

function wrapVerses(container) {
    // Group each verse's number + text into a single clickable .verse-span.
    // currentVerse is tracked across paragraphs so verses that continue onto
    // a new line (e.g. poetry) still get wrapped under the right verse.
    let currentVerse = null;
    const blocks = container.querySelectorAll('p');
    const targets = blocks.length ? blocks : [container];

    targets.forEach(block => {
        const children = Array.from(block.childNodes);
        let wrapper = null;
        children.forEach(node => {
            if (node.nodeType === 1 && node.classList && node.classList.contains('v')) {
                currentVerse = node.textContent.trim();
                wrapper = null;
            }
            if (currentVerse === null) {
                return; // leave headings / pre-verse content untouched
            }
            if (!wrapper || wrapper.dataset.verse !== currentVerse) {
                wrapper = document.createElement('span');
                wrapper.className = 'verse-span';
                wrapper.dataset.verse = currentVerse;
                block.insertBefore(wrapper, node);
            }
            wrapper.appendChild(node);
        });
    });
}

function on(parent, event, selector, handler) {
    parent.addEventListener(event, function(e) {
        const target = e.target.closest(selector);
        if (target && parent.contains(target)) {
            handler(e, target);
        }
    });
}

function closest(el, selector) {
    if (!el) return null;
    return el.closest(selector);
}

function updateLayout() {
    const wrapper = document.getElementById('wrapper');
    const panels  = wrapper.querySelectorAll('.biblePanel');

    wrapper.classList.toggle('grid-2x2', panels.length >= 4);
    wrapper.classList.toggle('grid-5plus', panels.length >= 5);
}

function selectNext(select) {
    if (select.selectedIndex < select.options.length - 1) {
        select.selectedIndex++;
    }
}

function selectPrev(select) {
    if (select.selectedIndex > 0) {
        select.selectedIndex--;
    }
}



</script>

<template id="biblePanelTemplate">
	<div class="biblePanel gold-border" id="{{biblePanelId}}">
        <div class="bibleTopWrapper">
            <div class="bibleClose">
                <div class="bibleCloseButton">X</div>
            </div>
            <div class="bibleControls">
                <select class="bibleSelector">

                </select>
                <select class="bookSelector">
                    <option values="">Book</option>
                </select>
                <select class="chapterSelector">
                    <option value="">Chapter</option>
                </select>
            </div>
            <div class="panelMenu">
                <div class="panelMenuButton" aria-label="Panel menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <div class="panelMenuDropdown">
                    <button type="button" class="panelMenuItem historyBack" disabled>
                        <span class="panelMenuIcon">&larr;</span>
                        <span>back</span>
                    </button>
                    <button type="button" class="panelMenuItem historyForward" disabled>
                        <span class="panelMenuIcon">&rarr;</span>
                        <span>forward</span>
                    </button>
                    <hr class="panelMenuDivider">
                    <button type="button" class="panelMenuItem zoomOut">
                        <span class="panelMenuIcon">&minus;</span>
                        <span>smaller</span>
                    </button>
                    <button type="button" class="panelMenuItem zoomIn">
                        <span class="panelMenuIcon">+</span>
                        <span>larger</span>
                    </button>
                </div>
            </div>
        </div>
		<div class="biblePanelWindow">
		
		</div>
        <div class="panelNav panelLeft">←</div>
        <div class="panelNav panelRight">→</div>
    </div>
</template>

</body>
</html>