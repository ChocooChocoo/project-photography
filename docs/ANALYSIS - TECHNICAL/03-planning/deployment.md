# Planning Deployment Notes

> **In plain terms:** Production credentials and settings stay outside the source code, and public uploads keep using the existing storage approach. This documentation does not approve a deployment change.

Use environment configuration for credentials and production settings. Keep public uploads on the configured `public` disk; do not add a storage symlink. Database and payment changes require their normal migration, configuration, and provider-verification paths; none are authorized by this documentation reset.
