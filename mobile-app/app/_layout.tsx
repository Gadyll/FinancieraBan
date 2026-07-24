import React, { useEffect } from "react";
import { Stack, useRouter, useSegments } from "expo-router";
import { StatusBar } from "expo-status-bar";
import { AuthProvider, useAuth } from "../context/AuthContext";
import { ActivityIndicator, View, StyleSheet } from "react-native";

function RootLayoutNav() {
  const { user, isLoading, otpPending } = useAuth();
  const segments = useSegments();
  const router = useRouter();

  useEffect(() => {
    if (isLoading) return;

    const currentRoute = segments[0] as string;
    const inTabs = currentRoute === "(tabs)";
    const inLogin = currentRoute === "login";
    const inVerifyOtp = currentRoute === "verify-otp";

    if (otpPending) {
      // Hay un OTP pendiente: ir a pantalla de verificacion si no estamos ya ahi
      if (!inVerifyOtp) {
        router.replace("/verify-otp" as any);
      }
      return;
    }

    if (!user) {
      // No autenticado: ir a login
      if (!inLogin) {
        router.replace("/login" as any);
      }
    } else {
      // Autenticado: ir a tabs si estamos en login o verify-otp
      if (inLogin || inVerifyOtp) {
        router.replace("/(tabs)" as any);
      }
    }
  }, [user, isLoading, otpPending, segments]);

  if (isLoading) {
    return (
      <View style={styles.loadingCenter}>
        <ActivityIndicator size="large" color="#1a6fcf" />
      </View>
    );
  }

  return (
    <>
      <Stack screenOptions={{ headerShown: false }}>
        <Stack.Screen name="login" options={{ headerShown: false, animation: "fade" }} />
        <Stack.Screen name="verify-otp" options={{ headerShown: false, animation: "slide_from_right" }} />
        <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
      </Stack>
      <StatusBar style="auto" />
    </>
  );
}

export default function RootLayout() {
  return (
    <AuthProvider>
      <RootLayoutNav />
    </AuthProvider>
  );
}

const styles = StyleSheet.create({
  loadingCenter: {
    flex: 1,
    justifyContent: "center",
    alignItems: "center",
    backgroundColor: "#f1f5f9",
  },
});
