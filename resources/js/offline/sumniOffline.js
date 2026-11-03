const DATABASE_NAME = 'northlink-lact-offline'
const DATABASE_VERSION = 1
const COLLECTION_STORE = 'collections'
const ROUTE_RUN_STORE = 'routeRuns'
const SNAPSHOT_STORE = 'routeSnapshots'
const DEVICE_KEY = 'northlink-device-uuid'
const OFFLINE_OWNER_KEY = 'northlink-offline-owner'
const OFFLINE_CACHE = 'northlink-field-ready-v1'
const PRIVATE_STORAGE_PREFIX = 'northlink-'

const openDatabase = () => new Promise((resolve, reject) => {
  const request = indexedDB.open(DATABASE_NAME, DATABASE_VERSION)

  request.onupgradeneeded = () => {
    const database = request.result

    if (!database.objectStoreNames.contains(COLLECTION_STORE)) {
      const store = database.createObjectStore(COLLECTION_STORE, { keyPath: 'uuid' })
      store.createIndex('routeDate', ['routeId', 'collectionDate'])
      store.createIndex('syncStatus', 'syncStatus')
    }

    if (!database.objectStoreNames.contains(ROUTE_RUN_STORE)) {
      database.createObjectStore(ROUTE_RUN_STORE, { keyPath: 'key' })
    }

    if (!database.objectStoreNames.contains(SNAPSHOT_STORE)) {
      database.createObjectStore(SNAPSHOT_STORE, { keyPath: 'key' })
    }
  }

  request.onsuccess = () => resolve(request.result)
  request.onerror = () => reject(request.error)
})

const requestResult = (request) => new Promise((resolve, reject) => {
  request.onsuccess = () => resolve(request.result)
  request.onerror = () => reject(request.error)
})

const transactionComplete = (transaction) => new Promise((resolve, reject) => {
  transaction.oncomplete = () => resolve()
  transaction.onerror = () => reject(transaction.error)
  transaction.onabort = () => reject(transaction.error)
})

const createUuid = () => {
  if (crypto.randomUUID) {
    return crypto.randomUUID()
  }

  return '10000000-1000-4000-8000-100000000000'.replace(/[018]/g, (character) =>
    (character ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> character / 4).toString(16)
  )
}

export const getDeviceUuid = () => {
  let deviceUuid = localStorage.getItem(DEVICE_KEY)

  if (!deviceUuid) {
    deviceUuid = createUuid()
    localStorage.setItem(DEVICE_KEY, deviceUuid)
  }

  return deviceUuid
}

export const saveRouteSnapshot = async ({ route, clients, today }) => {
  const database = await openDatabase()
  const transaction = database.transaction(SNAPSHOT_STORE, 'readwrite')

  transaction.objectStore(SNAPSHOT_STORE).put({
    key: `${route.id}:${today}`,
    route,
    clients,
    today,
    savedAt: new Date().toISOString(),
  })

  await transactionComplete(transaction)
}

export const offlineReadyKey = (routeId, today) => `northlink-offline-ready:${routeId}:${today}`

export const isRouteReadyOffline = (routeId, today) => (
  localStorage.getItem(offlineReadyKey(routeId, today)) === 'true'
)

export const prepareRouteForOffline = async ({ routeId, today }) => {
  if (!('serviceWorker' in navigator) || !('caches' in window)) {
    throw new Error('Este navegador no permite preparar la ruta para trabajar sin conexión.')
  }

  if (!window.isSecureContext) {
    throw new Error('La descarga offline requiere abrir Northlink mediante HTTPS.')
  }

  const registration = await navigator.serviceWorker.ready
  await registration.update().catch(() => {})

  const cache = await caches.open(OFFLINE_CACHE)
  const currentUrl = new URL(window.location.href)
  currentUrl.search = ''

  const resourceUrls = performance.getEntriesByType('resource')
    .map(({ name }) => name)
    .filter((url) => {
      const resource = new URL(url, window.location.origin)

      return resource.origin === window.location.origin
        && (resource.pathname.startsWith('/build/')
          || resource.pathname === '/favicon.ico'
          || resource.pathname === '/manifest.webmanifest')
    })

  const urls = [...new Set([
    currentUrl.href,
    `${window.location.origin}/manifest.webmanifest`,
    `${window.location.origin}/favicon.ico`,
    ...resourceUrls,
  ])]

  const results = await Promise.allSettled(urls.map(async (url) => {
    const response = await fetch(url, { credentials: 'same-origin', cache: 'reload' })
    if (!response.ok) throw new Error(`No se pudo descargar ${url}`)
    await cache.put(url, response)
  }))

  const failed = results.filter(({ status }) => status === 'rejected')
  if (failed.length > 0) {
    throw new Error('No se descargaron todos los archivos. Revisa el internet e inténtalo nuevamente.')
  }

  const persistent = await navigator.storage?.persist?.().catch(() => false)
  localStorage.setItem(offlineReadyKey(routeId, today), 'true')

  return { persistent: Boolean(persistent), resources: urls.length }
}

export const clearPrivateOfflineData = async () => {
  const database = await openDatabase().catch(() => null)

  if (database) {
    const storeNames = [COLLECTION_STORE, ROUTE_RUN_STORE, SNAPSHOT_STORE]
      .filter((storeName) => database.objectStoreNames.contains(storeName))

    if (storeNames.length > 0) {
      const transaction = database.transaction(storeNames, 'readwrite')
      storeNames.forEach((storeName) => transaction.objectStore(storeName).clear())
      await transactionComplete(transaction)
    }

    database.close()
  }

  Object.keys(localStorage)
    .filter((key) => key.startsWith(PRIVATE_STORAGE_PREFIX))
    .forEach((key) => localStorage.removeItem(key))

  if ('caches' in window) {
    const cacheNames = await caches.keys()
    await Promise.all(cacheNames
      .filter((cacheName) => cacheName.startsWith(PRIVATE_STORAGE_PREFIX))
      .map((cacheName) => caches.delete(cacheName)))
  }

  navigator.serviceWorker?.controller?.postMessage({ type: 'CLEAR_PRIVATE_DATA' })
}

export const ensureOfflineOwner = async (userId) => {
  if (!userId) {
    return
  }

  const owner = String(userId)
  const existingOwner = localStorage.getItem(OFFLINE_OWNER_KEY)

  if (existingOwner !== owner) {
    await clearPrivateOfflineData()
    localStorage.setItem(OFFLINE_OWNER_KEY, owner)
  }
}

export const enqueueCollection = async ({ routeId, collectionDate, producerId, liters, temperature }) => {
  const database = await openDatabase()
  const transaction = database.transaction(COLLECTION_STORE, 'readwrite')
  const collection = {
    uuid: createUuid(),
    routeId,
    producerId,
    collectionDate,
    liters: Number(liters),
    temperature: Number(temperature),
    notes: 'Capturado desde Firefox sin conexión garantizada.',
    syncStatus: 'pending',
    createdAt: new Date().toISOString(),
    lastError: null,
  }

  transaction.objectStore(COLLECTION_STORE).put(collection)
  await transactionComplete(transaction)

  return collection
}

export const getRouteCollections = async (routeId, collectionDate) => {
  const database = await openDatabase()
  const transaction = database.transaction(COLLECTION_STORE, 'readonly')
  const index = transaction.objectStore(COLLECTION_STORE).index('routeDate')

  return requestResult(index.getAll(IDBKeyRange.only([routeId, collectionDate])))
}

const getOrCreateRouteRun = async (database, routeId, runDate) => {
  const key = `${routeId}:${runDate}`
  const readTransaction = database.transaction(ROUTE_RUN_STORE, 'readonly')
  let routeRun = await requestResult(readTransaction.objectStore(ROUTE_RUN_STORE).get(key))

  if (!routeRun) {
    routeRun = {
      key,
      uuid: createUuid(),
      routeId,
      runDate,
      startedAt: new Date().toISOString(),
      syncStatus: 'pending',
    }

    const writeTransaction = database.transaction(ROUTE_RUN_STORE, 'readwrite')
    writeTransaction.objectStore(ROUTE_RUN_STORE).put(routeRun)
    await transactionComplete(writeTransaction)
  }

  return routeRun
}

const updateCollections = async (database, collections, syncStatus, lastError = null) => {
  const transaction = database.transaction(COLLECTION_STORE, 'readwrite')
  const store = transaction.objectStore(COLLECTION_STORE)

  collections.forEach((collection) => {
    store.put({
      ...collection,
      syncStatus,
      lastError,
      syncedAt: syncStatus === 'synced' ? new Date().toISOString() : collection.syncedAt,
    })
  })

  await transactionComplete(transaction)
}

export const syncRouteCollections = async ({ routeId, runDate, endpoint, csrfToken }) => {
  const database = await openDatabase()
  const allCollections = await getRouteCollections(routeId, runDate)
  const pendingCollections = allCollections.filter(({ syncStatus }) => syncStatus === 'pending' || syncStatus === 'failed')

  if (pendingCollections.length === 0) {
    return { synced: 0, response: null }
  }

  const routeRun = await getOrCreateRouteRun(database, routeId, runDate)
  const payload = {
    device_uuid: getDeviceUuid(),
    device_name: `Firefox SUNMI · ${navigator.userAgent.slice(0, 80)}`,
    platform: 'firefox-android',
    route_run: {
      uuid: routeRun.uuid,
      route_id: routeId,
      run_date: runDate,
      status: 'in_progress',
      started_at: routeRun.startedAt,
      completed_at: null,
    },
    collections: pendingCollections.map((collection) => ({
      uuid: collection.uuid,
      producer_id: collection.producerId,
      collection_date: collection.collectionDate,
      liters: collection.liters,
      temperature: collection.temperature,
      notes: collection.notes,
    })),
  }

  try {
    const response = await fetch(endpoint, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
      body: JSON.stringify(payload),
    })

    const data = await response.json().catch(() => ({}))

    if (!response.ok) {
      const message = data.message || (response.status === 419
        ? 'La sesión venció. Conéctate e inicia sesión nuevamente.'
        : 'El servidor no pudo sincronizar los acopios.')
      const status = response.status === 409 ? 'conflict' : 'failed'
      await updateCollections(database, pendingCollections, status, message)
      throw new Error(message)
    }

    await updateCollections(database, pendingCollections, 'synced')

    return { synced: pendingCollections.length, response: data }
  } catch (error) {
    if (error instanceof TypeError) {
      await updateCollections(database, pendingCollections, 'pending', 'Sin conexión. Se reintentará automáticamente.')
    }

    throw error
  }
}
