# Architecture

The app uses a pragmatic layered architecture: immutable data models, a centralized Dio client, feature repositories, Riverpod application state, GoRouter guards, feature screens, and shared widgets. Widgets do not call HTTP APIs or parse JSON. Authentication, the current user, permissions, feature flags, dashboard state, and unread count are centralized through providers.

Online-first startup reads the secure token, loads system information, validates the token with `/auth/user`, then enters protected navigation. A missing or rejected token returns to login. A network failure shows an online-required retry screen and never unlocks protected content.

Repositories are the future compatibility boundary for Phase 11C. Offline persistence and synchronization can be added behind these contracts without moving API parsing into widgets. Phase 11B has no local operational database or mutation queue.
