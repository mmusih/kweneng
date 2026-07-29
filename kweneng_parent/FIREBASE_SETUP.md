# Firebase local setup

Firebase configuration is generated locally and must not be committed.

## Configure the parent app

1. Install and authenticate the Firebase and FlutterFire CLIs.
2. From `kweneng_parent`, run:

   ```text
   flutterfire configure
   ```

3. Select the correct Firebase project and Android application.
4. Confirm that these local files were generated:

   - `lib/firebase_options.dart`
   - `android/app/google-services.json`

Both files are ignored by Git.

## Security

- Never commit service-account JSON, signing keys, `key.properties`, or `.env` files.
- Restrict client API keys in Google Cloud to the expected Android package and signing certificate.
- Rotate any key that has appeared in Git history before using the application again.
