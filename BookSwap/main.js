const { app, BrowserWindow, ipcMain } = require('electron');
const path = require('path');

let mainWindow;

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1200,
        height: 800,
        minWidth: 800,
        minHeight: 600,
        webPreferences: {
            nodeIntegration: false,
            contextIsolation: true,
            preload: path.join(__dirname, 'preload.js')
        },
        frame: true,
        resizable: true,
        show: false
    });
    
    // Clear cache on load
    mainWindow.webContents.session.clearCache();
    
    // Load login page initially
    mainWindow.loadFile(path.join(__dirname, 'pages', 'login.html'));

    mainWindow.once('ready-to-show', () => {
        mainWindow.show();
    });

    mainWindow.on('closed', () => {
        mainWindow = null;
    });
}

app.whenReady().then(() => {
    createWindow();

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) {
            createWindow();
        }
    });
});

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

// Handle navigation events
ipcMain.on('navigate', (event, page) => {
    const pages = {
        'login': 'login.html',
        'register': 'register.html',
        'forgot-password': 'forgot-password.html',
        'dashboard': 'admin.html'  // Navigate to admin page
    };
    
    const filename = pages[page] || `${page}.html`;
    mainWindow.loadFile(path.join(__dirname, 'pages', filename));
});