src/
├── assets/             <-- CSS, Images
├── components/         <-- Global reusable components (Buttons, Inputs)
│   └── ui/
├── layouts/            <-- Wrapper components (MainLayout.vue, GuestLayout.vue)
├── router/             <-- Navigation (index.ts)
├── stores/             <-- State Management (auth.ts, city.ts)
├── types/              <-- TypeScript Interfaces
└── views/              <-- "Pages"
    ├── auth/
    │   └── LoginView.vue
    ├── dashboard/
    │   └── DashboardView.vue
    └── cities/         <-- Feature Folder
        ├── CityListView.vue
        └── components/ <-- Local components just for Cities
            └── CityModal.vue